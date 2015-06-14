<?php
/**
 * Ads controller.
 *
 * @link http://epi.uj.edu.pl
 * @author epi(at)uj(dot)edu(dot)pl
 * @copyright EPI 2015
 */

namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert; //- jak jest formularz w osobnym pliku to bez tego!

use Model\AdsModel;
use Form\AdForm;
use Model\CategoriesModel;
use Model\UsersModel;
use Model\PhotosModel;

class AdsController implements ControllerProviderInterface
{
    /**
     * Routing settings.
     *
     * @access public
     * @param  Silex\Application $app Silex application
     * @return $adsController
     */
    public function connect(Application $app)
    {
        $adsController = $app['controllers_factory'];
        $adsController->get('/', array($this, 'indexAction'))
            ->bind('/ads/');
        $adsController->match('/add', array($this, 'addAction'))
            ->bind('/ads/add');
        $adsController->match('/edit/{id}', array($this, 'editAction'))
            ->bind('/ads/edit');
        $adsController->match('/delete/{id}', array($this, 'deleteAction'))
            ->bind('/ads/delete');
        $adsController->get('/view/{id}', array($this, 'viewAction'))
            ->bind('/ads/view');
        return $adsController;
    }

    /**
     * Index action.
     *
     * @access public
     * @param  Silex\Application $app Silex application
     * @param  Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function indexAction(Application $app, Request $request)
    {
        $usersModel = new UsersModel($app);
        $idLoggedUser = $usersModel->getIdCurrentUser($app);

        $this->view['loggedUser'] = $idLoggedUser;
        // $view = array();
        // $adsModel = new AdsModel($app);

        // try {
            // $view['ads'] = $adsModel->getAll();
        // } catch (\Exception $e) {
            // $errors[] = 'Something went wrong';

            // $app['session']->getFlashBag()->add(
                // 'message',
                // array(
                    // 'type' => 'danger',
                    // 'content' => 'Ads not found'
                // )
            // );
            // return $app['twig']->render(
                // 'errors/404.twig'
            // );
        // }
        // return $app['twig']
            // ->render('ads/index.twig', $view);
        $pageLimit = 5;
        $page = (int) $request->get('page', 1);
        $adsModel = new AdsModel($app);
        try {
            $pagesCount = $adsModel->countAdsPages($pageLimit);
            $page = $adsModel->getCurrentPageNumber($page, $pagesCount);
            $ads = $adsModel->getAdsPage($page, $pageLimit);
            $this->view['paginator']
                = array('page' => $page, 'pagesCount' => $pagesCount);
            $this->view['ads'] = $ads;
        } catch (\Exception $e) {
            $errors[] = 'Something went wrong';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Ads not found'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }
        return $app['twig']->render('ads/index.twig', $this->view);
    }

    /**
     * Add action.
     *
     * @access public
     * @param  Silex\Application $app Silex application
     * @param  Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function addAction(Application $app, Request $request)
    {
        $usersModel = new UsersModel($app);
        $idLoggedUser = $usersModel->getIdCurrentUser($app);

        $categoriesModel = new CategoriesModel($app);
        $choiceCategory = $categoriesModel->getCategoriesList();

        $datetime = date('Y-m-d H:i:s');

        $data = array(
            'postDate' => $datetime,
            'user_id' => $idLoggedUser
        );


        $form = $app['form.factory']->createBuilder('form', $data)
            ->add(
                'title',
                'text',
                array(
                    'constraints' => array(
                        new Assert\NotBlank(), new Assert\Length(
                            array(
                                'min' => 3,
                                'max' => 30,
                                'minMessage' =>'Use more than 2 characters',
                                'maxMessage' =>'Use less than 30 characters',

                            )
                        )
                    )
                )
            )
            ->add(
                'text',
                'textarea',
                array(
                    'constraints' => array(
                        new Assert\NotBlank(),new Assert\Length(
                            array(
                                'min' => 5,
                                'minMessage' =>'Use more than 5 characters',

                            )
                        )
                    )
                )
            )
            ->add(
                'category_id',
                'choice',
                array(
                    'choices' => $choiceCategory
                )
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $adsModel = new AdsModel($app);
            $data = $form->getData();

            $catId=$data['category_id'];
            // $categoriesModel = new CategoriesModel($app);
            // $catList = $categoriesModel->getAll();
            // $cat=$catList[$catId];
            // $category=$cat['id'];
            // $data['category_id']=$category;

            $adsModel->saveAd($data);

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'success',
                    'content' => 'Your advertisement has been added.'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate('/ads/'),
                301
            );
        }
        return $app['twig']->render(
            'ads/add.twig',
            array(
                'form' => $form->createView()
            )
        );
    }

    /**
     * Edit action.
     *
     * @access public
     * @param  Silex\Application $app Silex application
     * @param  Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function editAction(Application $app, Request $request)
    {
        $adsModel = new AdsModel($app);
        $id = (int) $request->get('id', 0);
        $ad = $adsModel->getAd($id);

        if (!$ad) {
            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Ad not found'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }
       
        $usersModel = new UsersModel($app);
        $idLoggedUser = $usersModel->getIdCurrentUser($app);
        if (!$app['security']->isGranted('ROLE_ADMIN')) {
            if ((int)$ad['user_id'] !== (int)$idLoggedUser) {
                // echo 'This is not your ad - you can not edit it.';
                // redirect($app['url_generator']->generate('/ads/'), 301);
                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'danger',
                        'content' => 'This is not your ad - you can not edit it.'
                    )
                );
                return $app['twig']->render(
                    'errors/403.twig'
                );
            }
        }

        $categoriesModel = new CategoriesModel($app);
        $choiceCategory = $categoriesModel->getCategoriesList();

        $data = array(
            'id'=> $id,
            'title' => $ad['title'],
            'text' => $ad['text'],
            'category_id' => $ad['ategory_id'],
            'postDate' => $ad['postDate'],
            'user_id' => $ad['user_id']
        );


        try {
            $form = $app['form.factory']->createBuilder(
                'form',
                $data
            )
                ->add(
                    'category_id',
                    'choice',
                    array(
                        'choices' => $choiceCategory
                    )
                )
                ->add(
                    'title',
                    'text',
                    array(
                        'constraints' => array(
                            new Assert\NotBlank(), new Assert\Length(
                                array(
                                    'min' => 5
                                )
                            )
                        )
                    )
                )
                ->add(
                    'text',
                    'textarea',
                    array(
                        'constraints' => array(
                            new Assert\NotBlank(), new Assert\Length(
                                array(
                                    'min' => 5
                                )
                            )
                        )
                    )
                )
                // ->add('save', 'submit')
                ->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {
                $adsModel = new AdsModel($app);
                $data = $form->getData();

                $catId=$data['category_id'];
                // $categoriesModel = new CategoriesModel($app);
                // $catList = $categoriesModel->getAll();
                // $cat=$catList[$catId];
                // $category=$cat['id'];
                // $data['category_id']=$category;



                $adsModel->updateAd($data);

                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'success',
                        'content' => 'Your ad has been edited.'

                    )
                );
                // return $app->redirect(
                    // $app['url_generator']->generate('/users/account'),
                    // 301
                // );
                return $app->redirect(
                    $app['url_generator']->generate('/ads/'),
                    301
                );

            }
        } catch (\Exception $e) {
            $errors[] = 'Something went wrong';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Ad not found. '
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }
        return $app['twig']->render(
            'ads/edit.twig',
            array(
                'form' => $form->createView()
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
    public function deleteAction(Application $app, Request $request)
    {
        // $form = $app['form.factory']
            // ->createBuilder(new AdForm(), $ad)->getForm();
        // $form->remove('title');
        // $form->remove('text');
        // // $view = array();
        // return $app['twig']->render('ads/delete.twig', $view);
        $usersModel = new UsersModel($app);
        $idLoggedUser = $usersModel->getIdCurrentUser($app);
		var_dump($ad['user_id']);
		var_dump($idLoggedUser);
        if (!$app['security']->isGranted('ROLE_ADMIN')) {
            if ((int)$ad['user_id'] !== (int)$idLoggedUser) {
                // echo 'This is not your ad - you can not edit it.';
                // redirect($app['url_generator']->generate('/ads/'), 301);
                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'danger',
                        'content' => 'This is not your ad - you can not delete it.'
                    )
                );
                return $app['twig']->render(
                    'errors/403.twig'
                );
            }
        }

        $id = (int) $request -> get('id', 0);
        $adsModel = new AdsModel($app);
        try {
            $adsModel -> deleteAd($id);
            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'success',
                    'content' => 'Ad has been deleted.'
                )
            );
        } catch (\Exception $e) {
            // $errors[] = 'Something went wrong';
            // return $app['twig']->render(
                // 'errors/500.twig'
            // );
            $app->abort(
                404,
                $app['translator']->trans('Ad not found')
            );
        }
        return $app->redirect(
            $app['url_generator']->generate('/ads/'),
            301
        );
    }

    /**
     * View action.
     *
     * @access public
     * @param  Silex\Application $app Silex application
     * @param  Symfony\Component\HttpFoundation\Request $request Request object
     *
     * @return string Output
     */
    public function viewAction(Application $app, Request $request)
    {
        $id = (int)$request->get('id', null);
        
        $usersModel = new UsersModel($app);
        $idLoggedUser = $usersModel->getIdCurrentUser($app);
        $number = $usersModel-> getPhone($idLoggedUser);
		
		
        
        $adsModel = new AdsModel($app);
        try {
            $ad = $adsModel->getAd($id);
            // var_dump($ad['user_id']);
		// var_dump($idLoggedUser);
            if (!$ad) {
                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'danger',
                        'content' => 'Ad not found'
                    )
                );
                return $app['twig']->render(
                    'errors/404.twig'
                );
            }
            
            $photoId = $ad['photo_id'];

            if ($photoId != null) {
                $photosModel = new PhotosModel($app);
                $photo= $photosModel->getFile($photoId);
            }
        } catch (\Exception $e) {
            $errors[] = 'Something went wrong';
            $app->abort(404, $app['translator']->trans('Ad not found'));

            return $app['twig']->render(
                'errors/404.twig'
            );
        }

        return $app['twig']->render(
            'ads/view.twig',
            array(
                'ad' => $ad,
                'photo' => $photo,
                'loggedUser' => $idLoggedUser,
                'number' => $number['phone_number'],
            )
        );
    }
}
