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
            $errors[] = 'Coś poszło nie tak podczas tworzenia formularza';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Coś poszło nie tak podczas tworzenia formularza'
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
                                'content' => 'To nie jest Twoje ogłoszenie - nie możesz dodać do niego zdjęcia.'
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
                            'content' => 'Zdjęcie zostało dodane.'
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
                            'content' => 'Nie można przesłać zdjęcia.'
                        )
                    );
                }

            } else {
                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'danger',
                        'content' => 'Formularz zawiera nieprawidłowe dane.'
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
                            'content' => 'To nie jest twoje ogłoszenie - nie możesz usunąć jego zdjęcia.'
                        )
                    );
                    return $app['twig']->render(
                        'errors/403.twig'
                    );
                }
            }
        } catch (\Exception $e) {
            $errors[] = 'Coś poszło nie tak podczas pobierania danych użytkownika';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Coś poszło nie tak podczas pobierania danych użytkownika'
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
            $errors[] = 'Coś poszło nie tak podczas tworzenia formularza';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Coś poszło nie tak podczas tworzenia formularza'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }
        
        if ($form->isValid()) {
            if ($form->get('Nie')->isClicked()) {
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
                            'content' => 'Zdjęcie zostało usunięte.'
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
                            'content' => 'Nie znaleziono zdjęcia'
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
