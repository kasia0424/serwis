<?php
/**
 * Ads controller.
 *
 * @link http://wierzba.wzks.uj.edu.pl/~12_sipel/serwis/web/ads/
 * @author Wanda Sipel
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
use Form\DeleteForm;

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
        try {
            $usersModel = new UsersModel($app);
            $idLoggedUser = $usersModel->getIdCurrentUser($app);

            $this->view['loggedUser'] = $idLoggedUser;
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
        // return $app['twig']
            // ->render('ads/index.twig', $view);
        $pageLimit = 5;
        $page = (int) $request->get('page', 1);
        
        try {
            $adsModel = new AdsModel($app);
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
        try {
            $usersModel = new UsersModel($app);
            $idLoggedUser = $usersModel->getIdCurrentUser($app);
            $number = $usersModel->getPhone($idLoggedUser);
            if ($number == null) {
                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'danger',
                        'content' => 'Add your phone number before.'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate('/user/number'),
                    301
                );
            }
        } catch (\Exception $e) {
            $errors[] = 'Something went wrong in getting user';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Something went wrong in getting user data'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }
        
        try {
            $categoriesModel = new CategoriesModel($app);
            $choiceCategory = $categoriesModel->getCategoriesList();

            $datetime = date('Y-m-d H:i:s');

            $data = array(
                'postDate' => $datetime,
                'user_id' => $idLoggedUser
            );

            $form = $app['form.factory']
                ->createBuilder(new DeleteForm(), $data)->getForm();

            $form = $app['form.factory']->createBuilder('form', $data)
                ->add(
                    'title',
                    'text',
                    array(
                        'attr' => array(
                             'placeholder' => 'Title',
                        ),
                        'label' => false,
                        'constraints' => array(
                            new Assert\NotBlank(), new Assert\Length(
                                array(
                                    'min' => 3,
                                    'max' => 30,
                                    'minMessage' =>'Use more than 2 characters',
                                    'maxMessage' =>'Use less than 30 characters',

                                )
                            ),
                            new Assert\Regex(
                                array(
                                    'pattern' => "/[a-zA-z]{3,}/",
                                    //'match' =>   true,
                                    'message' => 'It\'s your ad\'s title - use at least 3 letters in it.',
                                )
                            )
                        )
                    )
                )
                ->add(
                    'text',
                    'textarea',
                    array(
                        'attr' => array(
                             'placeholder' => 'Content of your ad',
                        ),
                        'label' => false,
                        'constraints' => array(
                            new Assert\NotBlank(),new Assert\Length(
                                array(
                                    'min' => 5,
                                    'minMessage' =>'Use more than 4 characters',

                                )
                            ),
                            new Assert\Regex(
                                array(
                                    'pattern' => "/[a-zA-z]{3,}/",
                                    'message' => 'It\'s your ad - use at least 3 letters in it.',
                                )
                            )
                        )
                    )
                )
                ->add(
                    'category_id',
                    'choice',
                    array(
                        'placeholder' => 'Choose category',
                        'choices' => $choiceCategory
                    )
                )
                // ->add(
                    // 'file',
                    // 'file',
                    // array(
                        // 'label' => 'Choose photo',
                        // 'required' => false,
                        // 'constraints' => array(new Assert\Image())
                    // )
                // )
                // ->add('Upload', 'submit')
                ->getForm();
            // $form = $app['form.factory']
                // ->createBuilder(new AdForm(), array('choiceCategory' =>$choiceCategory))->getForm();
            // $form->remove('id');
            //$form->remove('user_id');
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

        $form->handleRequest($request);

        if ($form->isValid()) {
            var_dump($data);
            try {
                //file
                // if ($form->get('Upload')->isClicked()) {
                    // $files = $request->files->get($form->getName());
                    // var_dump($files);

                    // if ($files != null) {
                        // $path = dirname(dirname(dirname(__FILE__))).'/web/media';
                        // $photosModel = new PhotosModel($app);
                        // $originalFilename = $files['image']->getClientOriginalName();
                        // $newFilename = $photosModel->createName($originalFilename);
                        // $files['image']->move($path, $newFilename);
                        
                        // $last = $adsModel->getLastAd();
                        // //var_dump($last);die();

                    // //$adId = $data['ad_id'];
                    
                        // $photosModel->saveFile($newFilename, $last['id']);
                    // }
                // } 
                //
                
                $adsModel = new AdsModel($app);
                $data = $form->getData();
                //$catId=$data['category_id'];

                $adsModel->saveAd($data);
                
                

                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'success',
                        'content' => 'Your ad has been added.'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate('/ads/'),
                    301
                );
            } catch (\Exception $e) {
                $errors[] = 'Something went wrong in processing data';

                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'danger',
                        'content' => 'Something went wrong in processing data'
                    )
                );
                return $app['twig']->render(
                    'errors/404.twig'
                );
            }
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
        try {
            $id = (int) $request->get('id', 0);
            $adsModel = new AdsModel($app);
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
        } catch (\Exception $e) {
            $errors[] = 'Something went wrong in getting data';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Something went wrong in getting data'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }
        

        $data = array(
            'id'=> $id,
            'title' => $ad['title'],
            'text' => $ad['text'],
            'category_id' => $ad['category_id'],
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
                        'placeholder' => 'Choose category',
                        'label' => 'Category',
                        'choices' => $choiceCategory
                    )
                )
                ->add(
                    'title',
                    'text',
                    array(
                        'attr' => array(
                             'placeholder' => 'Title',
                        ),
                        'constraints' => array(
                            new Assert\NotBlank(), new Assert\Length(
                                array(
                                    'min' => 3,
                                    'max' => 30,
                                    'minMessage' =>'Use more than 2 characters',
                                    'maxMessage' =>'Use less than 30 characters',

                                )
                            ),
                            new Assert\Regex(
                                array(
                                    'pattern' => "/[a-zA-z]{3,}/",
                                    //'match' =>   true,
                                    'message' => 'It\'s your ad\'s title - use at least 3 letters in it.',
                                )
                            )
                        )
                    )
                )
                ->add(
                    'text',
                    'textarea',
                    array(
                        'attr' => array(
                             'placeholder' => 'Content of your ad',
                        ),
                        'constraints' => array(
                            new Assert\NotBlank(),new Assert\Length(
                                array(
                                    'min' => 5,
                                    'minMessage' =>'Use more than 4 characters',

                                )
                            ),
                            new Assert\Regex(
                                array(
                                    'pattern' => "/[a-zA-z]{3,}/",
                                    'message' => 'It\'s your ad - use at least 3 letters in it.',
                                )
                            )
                        )
                    )
                )
                ->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {
                $adsModel = new AdsModel($app);
                $data = $form->getData();
                $catId=$data['category_id'];

                $adsModel->updateAd($data);

                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'success',
                        'content' => 'Your ad has been edited.'

                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/ads/view',
                        array('id'=> $id)
                    ),
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
        try {
            // $view = array();
            // return $app['twig']->render('ads/delete.twig', $view);
            $usersModel = new UsersModel($app);
            $idLoggedUser = $usersModel->getIdCurrentUser($app);

            $id = (int) $request -> get('id', 0);
            $user = (int) $request -> get('user', 0);

            if (!$app['security']->isGranted('ROLE_ADMIN')) {
                if ((int)$user !== (int)$idLoggedUser) {
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

        //
        try {
            $data = array();
            $form = $app['form.factory']
                ->createBuilder(new DeleteForm(), $ad)->getForm();
            // $form = $app['form.factory']->createBuilder('form', $data)
                // ->add(
                    // 'id',
                    // 'hidden',
                    // array(
                        // 'data' => $id,
                    // )
                // )
                // ->add('Yes', 'submit')
                // ->add('No', 'submit')
                // ->getForm();

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
                //$data = $form->getData();

                return $app->redirect(
                    $app['url_generator']->generate(
                        '/'
                    ),
                    301
                );
            } else {
                try {
                    $adsModel = new AdsModel($app);
                    $adsModel -> deleteAd($id);
                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'success',
                            'content' => 'Ad has been deleted.'
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            '/user/account'
                        ),
                        301
                    );
                } catch (\Exception $e) {
                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'danger',
                            'content' => 'Ad not found'
                        )
                    );
                    return $app['twig']->render('404.twig');
                }
            }
        }
        return $app['twig']->render(
            '/ads/delete.twig',
            array(
                'form' => $form->createView(),
                $data
            )
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
        try {
            $usersModel = new UsersModel($app);
            $idLoggedUser = $usersModel->getIdCurrentUser($app);
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
            $adsModel = new AdsModel($app);
            $ad = $adsModel->getAd($id);
            $number = $usersModel-> getPhone($ad['user_id']);

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

            $photosModel = new PhotosModel($app);
            $photoTab= $photosModel->getPhoto($ad['id']);
            $photo = $photoTab['name'];

        } catch (\Exception $e) {
            $errors[] = 'Something went wrong';

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
