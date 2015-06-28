<?php
/**
 * Photos controller.
 *
 * @link http://wierzba.wzks.uj.edu.pl/~12_sipel/serwis/web/photos/
 * @author Wanda Sipel
 * @copyright EPI 2015
 */

namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Model\PhotosModel;
use Model\AdsModel;
use Model\UsersModel;
use Form\FilesForm;
use Form\DeleteForm;

/**
 * Class PhotosController.
 *
 * @package Controller
 * @implements ControllerProviderInterface
 */
class PhotosController implements ControllerProviderInterface
{
    /**
     * Routing settings.
     *
     * @access public
     * @param Application $app Silex application
     * @return PhotosController Result
     */
    public function connect(Application $app)
    {
        $photosController = $app['controllers_factory'];
        $photosController->match('/', array($this, 'upload'));
        $photosController->match('/upload', array($this, 'upload'));
        $photosController->match('/delete/{id}', array($this, 'delete'))
            ->bind('/photos/delete');
        $photosController->get('/upload/{id}', array($this, 'upload'))
            ->value('id', 1)->bind('/photos/upload');
        $photosController->get('/{id}', array($this, 'upload'))
            ->bind('/photos/');
        return $photosController;
    }

    /**
     * Upload action.
     *
     * @access public
     * @param Application $app Silex application
     * @param Request $request Request object
     * @return string Output
     */
    public function upload(Application $app, Request $request)
    {
        try {
            $adId =(int)$request->get('id', 0);

            $adsModel = new AdsModel($app);
            $usersModel = new UsersModel($app);

            $form = $app['form.factory']
                ->createBuilder(new FilesForm(), array('ad_id'=>$adId))->getForm();
        } catch (\Exception $e) {
            $errors[] = 'Something went wrong while preparing form';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Something went wrong while preparing form'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }

        if ($request->isMethod('post')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    $data = $form->getData();
                    $files = $request->files->get($form->getName());
                    $path = dirname(dirname(dirname(__FILE__))).'/web/media';

                    $photosModel = new PhotosModel($app);

                    $adId = $data['ad_id'];
                    $ad = $adsModel->getAd($adId);
                    $idLoggedUser = $usersModel->getIdCurrentUser($app);
                    if ((int)$ad['user_id'] == (int)$idLoggedUser) {
                        $photo = $photosModel->getPhoto($adId);
                        
                        if ($photo == null) {
                            $photosModel->saveImage($files, $path, $adId);
                        } else {
                            $photosModel->updateImage($files, $path, $adId);
                        }
                        $flag= true;
                    } else {
                        $app['session']->getFlashBag()->add(
                            'message',
                            array(
                                'type' => 'danger',
                                'content' => 'This is not your ad - you can not add photo to it.'
                            )
                        );
                        return $app['twig']->render(
                            'errors/403.twig'
                        );
                    }

                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'success',
                            'content' => 'File successfully uploaded.'
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            '/ads/view',
                            array('id' => $data['ad_id'])
                        ),
                        301
                    );
                    $flag= true;

                } catch (Exception $e) {
                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'danger',
                            'content' => 'Can not upload file.'
                        )
                    );
                }

            } else {
                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'danger',
                        'content' => 'Form contains invalid data.'
                    )
                );
            }
        }

        return $app['twig']->render(
            'photos/upload.twig',
            array(
                'form' => $form->createView(),
                'id' => $adId
            )
        );
    }
    
    
    /**
     * Delete action.
     *
     * @access public
     * @param  Silex\Application $app Silex application
     * @param  Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function delete(Application $app, Request $request)
    {
        try {
            $usersModel = new UsersModel($app);
            $idLoggedUser = $usersModel->getIdCurrentUser($app);

            $id = (int) $request -> get('photo', 0);
            $adId = (int) $request -> get('id', 0);
            $user = (int) $request -> get('user', 0);

            if (!$app['security']->isGranted('ROLE_ADMIN')) {
                if ((int)$user !== (int)$idLoggedUser) {
                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'danger',
                            'content' => 'This is not your ad - you can not delete it\'s photo.'
                        )
                    );
                    return $app['twig']->render(
                        'errors/403.twig'
                    );
                }
            }
        } catch (\Exception $e) {
            $errors[] = 'Something went wrong in getting user';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Something went wrong in getting user'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }


        try {
            $data = array();
            $form = $app['form.factory']
                ->createBuilder(new DeleteForm(), $ad)->getForm();
            $form->handleRequest($request);

        } catch (\Exception $e) {
            $errors[] = 'Something went wrong in creating form';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Something went wrong in creating form'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }
        
        if ($form->isValid()) {
            if ($form->get('No')->isClicked()) {
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/'
                    ),
                    301
                );
            } else {
                try {
                    $photosModel = new PhotosModel($app);
                    $photo = $photosModel -> getPhoto($id);
                    $photosModel -> deletePhoto($id);

                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'success',
                            'content' => 'Photo has been deleted.'
                        )
                    );

                    return $app->redirect(
                        $app['url_generator']->generate(
                            '/ads/view',
                            array('id'=> $adId)
                        ),
                        301
                    );
                } catch (\Exception $e) {
                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'danger',
                            'content' => 'Photo not found'
                        )
                    );
                    return $app['twig']->render('404.twig');
                }
            }
        }
        return $app['twig']->render(
            '/photos/delete.twig',
            array(
                'form' => $form->createView(),
                $data
            )
        );

    }
}
