<?php
/**
 * Users controller.
 *
 * @link http://wierzba.wzks.uj.edu.pl/~12_sipel/serwis/web/user/
 * @author Wanda Sipel
 * @copyright EPI 2015
 */
namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Validator\Constraints as Assert;
use Model\UsersModel;
use Model\AdsModel;

class UsersController implements ControllerProviderInterface
{
    /**
     * UsersModel object.
     *
     * @var $model
     * $access protected
     */
    protected $model;

    protected function getCurrentUser($app)
    {
        $token = $app['security']->getToken();

        if (null !== $token) {
            $user = $token->getUser()->getUsername();
        }

        return $user;
    }

    /**
     * Routing settings.
     *
     * @access public
     * @param Application $app Silex application
     * @return PhotosController Result
     */
    public function connect(Application $app)
    {
        $this->model = new UsersModel($app);
        $usersController = $app['controllers_factory'];
        $usersController->get('/', array($this, 'accountAction'))
            ->bind('/user/account');
        $usersController->match('/add/', array($this, 'addAction'))
            ->bind('/user/add');
        $usersController->match('/delete/{id}', array($this, 'deleteAction'))
            ->bind('/user/delete');
        $usersController->get('/view/{id}', array($this, 'viewAction'))
            ->bind('/user/view');
        $usersController->match('/edit/', array($this, 'passwordAction'))
            ->bind('/user/edit');
        $usersController->match('/number/', array($this, 'numberAction'))
            ->bind('/user/number');
         $usersController->match('/role/{id}', array($this, 'roleAction'))
            ->bind('/user/role');
        $usersController->match('/panel/', array($this, 'indexAction'))
            ->bind('/user/panel');

        return $usersController;
    }

    /**
     * View list of users
     *
     * @param Application $app
     *
     * @access public
     * @return mixed generates page
     */
    public function indexAction(Application $app, Request $request)
    {
        $usersModel = new UsersModel($app);
        $adsTab = $usersModel->countUserAds();

        $pageLimit = 4;
        $page = (int) $request->get('page', 1);

        try {
            $pagesCount = $usersModel->countUsersPages($pageLimit);
            $page = $usersModel->getCurrentPageNumber($page, $pagesCount);
            $users = $usersModel->getUsersPage($page, $pageLimit);
            $paginator = array('page' => $page, 'pagesCount' => $pagesCount);
        } catch (\Exception $e) {
            $errors[] = 'Something went wrong';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Users not found'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }
        //
        return $app['twig']->render(
            'users/index.twig',
            array(
                'paginator' => $paginator,
                'users' => $users,
                'ads' => $adsTab
            )
        );
    }

    /**
     * Add new user
     *
     * @param Application $app application object
     * @param Request $request request
     *
     * @access public
     * @return mixed Generates page or redirect
     */
    public function addAction(Application $app, Request $request)
    {
        $role =(int) 2;
        $data = array(
            'role_id' => $role
        );

        $form = $app['form.factory']->createBuilder('form', $data)
            ->add(
                'login',
                'text',
                array(
                    'constraints' => array(
                        new Assert\NotBlank(), new Assert\Length(
                            array(
                                'min' => 5,
                                'minMessage' =>
                                    'Use more than 4 characters',
                            )
                        )
                    )
                )
            )
            ->add(
                'password',
                'password',
                array(
                    'constraints' => array(
                        new Assert\NotBlank(), new Assert\Length(
                            array(
                                'min' => 5,
                                'minMessage' =>
                                    'Use more than 4 characters',
                            )
                        )
                    )
                )
            )
            ->add(
                'confirm_password',
                'password',
                array(
                    'constraints' => array(
                        new Assert\NotBlank(), new Assert\Length(
                            array(
                                'min' => 5,
                                'minMessage' =>
                                    'Use more than 4 characters',
                            )
                        )
                    )
                )
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            $data['login'] = $app
                ->escape($data['login']);
            $data['password'] = $app
                ->escape($data['password']);
            $data['confirm_password'] = $app
                ->escape($data['confirm_password']);

            if ($data['password'] === $data['confirm_password']) {
                $password = $app['security.encoder.digest']
                    ->encodePassword("{$data['password']}", '');

                $checkLogin = $this->model->getUserByLogin(
                    $data['login']
                );

                if (!$checkLogin === $checkLogin || !$checkLogin) {
                    try {
                        $this->model->addUser(
                            $form->getData(),
                            $password
                        );

                        $app['session']->getFlashBag()->add(
                            'message',
                            array(
                                'type' => 'success',
                                'content' => 'Account has been
                                 created. You can login now.'
                            )
                        );

                        return $app->redirect(
                            $app['url_generator']
                                ->generate(
                                    'auth_login'
                                ),
                            301
                        );
                    } catch (\Exception $e) {
                        $app['session']->getFlashBag()->add(
                            'message',
                            array(
                                'type' => 'warning',
                                'content' => 'Something went wrong. User was not created'
                            )
                        );
                        return $app['twig']->render(
                            'errors/500.twig'
                        );
                    }
                } else {
                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'warning',
                            'content' => 'This login is already taken.'
                        )
                    );
                    return $app['twig']->render(
                        'users/add.twig',
                        array(
                            'form' => $form->createView()
                        )
                    );
                }
            } else {
                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'warning',
                        'content' => 'Passwords are not correct.'
                    )
                );

                return $app['twig']->render(
                    'users/add.twig',
                    array(
                        'form' => $form->createView()
                    )
                );
            }
        }
        return $app['twig']->render(
            'users/add.twig',
            array(
                'form' => $form->createView()
            )
        );

    }
    /**
     * Edit user account
     *
     * @param Application $app application object
     * @param Request $request request
     *
     * @access public
     * @return mixed Generates page or redirect
     */
    public function editAction(Application $app, Request $request)
    {
        $usersModel = new UsersModel($app);
        $idLoggedUser = $usersModel->getIdCurrentUser($app);

        if ($app['security']->isGranted('ROLE_ADMIN')) {
            $usersModel = new UsersModel($app);
            $id = (int) $request->get('id', 0);
        } else {
            $id = $idLoggedUser;
        }

        $user = $usersModel->getUser($id);
        $token = $app['security']->getToken();
        $loggedUser = $token->getUser()->getUsername();
        $currentUser = $this->model->getUserByLogin($loggedUser);

        if (!$app['security']->isGranted('ROLE_ADMIN')) {
            if ((int)$currentUser['id'] !== (int)$id) {
                echo 'You can not edit this account';
                redirect($app['url_generator']->generate('/ads/'), 301);
            }
        }

        $data = array(
            'id' => $user['id'],
            'login' => $user['login'],
            'password' => '',
            'confirm_password' => '',
        );

        if (count($user)) {
            $form = $app['form.factory']->createBuilder('form', $data)
                ->add(
                    'login',
                    'text',
                    array(
                        'constraints' => array(
                            new Assert\NotBlank(), new Assert\Length(
                                array(
                                    'min' => 5,
                                    'minMessage' =>
                                        'Use more than 4 characters',
                                )
                            )
                        )
                    )
                )
                ->add(
                    'password',
                    'password',
                    array(
                        'constraints' => array(
                            new Assert\NotBlank(), new Assert\Length(
                                array(
                                    'min' => 5,
                                    'minMessage' =>
                                        'Use more than 4 characters',
                                )
                            )
                        )
                    )
                )
                ->add(
                    'confirm_password',
                    'password',
                    array(
                        'constraints' => array(
                            new Assert\NotBlank(), new Assert\Length(
                                array(
                                    'min' => 5,
                                    'minMessage' =>
                                        'Use more than 4 characters',
                                )
                            )
                        )
                    )
                )
                ->add('save', 'submit')
                ->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                $data['login'] = $app
                    ->escape($data['login']);
                $data['password'] = $app
                    ->escape($data['password']);
                $data['confirm_password'] = $app
                    ->escape($data['confirm_password']);

                if ($data['password'] === $data['confirm_password']) {
                    $password = $app['security.encoder.digest']
                        ->encodePassword("{$data['password']}", '');

                    $checkLogin = $this->model
                        ->getUserByLogin(
                            $data['login']
                        );

                    if ($data['login'] === $checkLogin ||
                        !$checkLogin ||
                        (int)$user['id'] ===(int)$checkLogin['id']) {
                        try {
                            $this->model->saveUser($data, $password);

                            $app['session']->getFlashBag()->add(
                                'message',
                                array(
                                    'type' => 'success',
                                    'content' => 'Account edited.'
                                )
                            );
                            return $app->redirect(
                                $app['url_generator']->generate(
                                    '/users/view'
                                ),
                                301
                            );
                        } catch (\Exception $e) {
                            $errors[] = 'Something went wrong';
                            
                            $app['session']->getFlashBag()->add(
                                'message',
                                array(
                                    'type' => 'danger',
                                    'content' => 'Something went wrong. '
                                )
                            );
                            return $app['twig']->render(
                                'errors/500.twig'
                            );
                        }
                    }

                }
            }

                return $app['twig']->render(
                    'users/edit.twig',
                    array(
                        'form' => $form->createView()
                    )
                );
        } else {
            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'User not found'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/users/view'
                ),
                301
            );
        }

    }
    
    
     /**
     * Edit user phone number
     *
     * @param Application $app application object
     * @param Request $request request
     *
     * @access public
     * @return mixed Generates page or redirect
     */
    public function numberAction(Application $app, Request $request)
    {
        //$id = (int) $request->get('id', 0);
        $id = $this->model->getIdCurrentUser($app);

        $usersModel = new UsersModel($app);
        $user = $usersModel->getUser($id);
        $token = $app['security']->getToken();
        
        $phone = $usersModel->getPhone($id);

        $data = array(
            'id' => $user['id']
        );

        if (count($user)) {
            $form = $app['form.factory']->createBuilder('form', $data)
                ->add(
                    'phone_number',
                    'text',
                    array(
                        'constraints' => array(
                            new Assert\NotBlank(), new Assert\Length(
                                array(
                                    'min' => 10,
                                    'max' => 12,
                                    'minMessage' =>
                                        'Use exactelty 10 numbers',
                                    'maxMessage' =>
                                        'Use exactelty 10 numbers',
                                )
                            ),
                            new Assert\Regex(
                                array(
                                    'pattern' => "/^\(?([0-9]{3})\)?([ .-]?)([0-9]{3})([ .-]?)([0-9]{4})$/",
                                    //'match' =>   true,
                                    'message' => 'Use only numbers - format: xxx xxx xxxx',
                                )
                            )
                        )
                    )
                )
                ->add('save', 'submit')
                ->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                $data['id'] = $app
                    ->escape($data['id']);
                $data['phone_number'] = $app
                    ->escape($data['phone_number']);

                try {
                    if ($phone != null) {
                        $this->model->updatePhone($data);
                    } else {
                        $this->model->addDetails($data);
                    };

                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'success',
                            'content' => 'Account edited.'
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            '/user/account'
                        ),
                        301
                    );
                } catch (\Exception $e) {
                    $errors[] = 'Something went wrong';
                    
                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'danger',
                            'content' => 'Something went wrong. '
                        )
                    );
                    return $app['twig']->render(
                        'errors/500.twig'
                    );
                }
            }

                return $app['twig']->render(
                    'users/number.twig',
                    array(
                        'form' => $form->createView(),
                        'user' => $user,
                        'number' => $phone['phone_number']
                    )
                );
        } else {
            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'User not found'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/user/view'
                ),
                301
            );
        }
    }


     /**
     * Edit user role
     *
     * @param Application $app application object
     * @param Request $request request
     *
     * @access public
     * @return mixed Generates page or redirect
     */
    public function roleAction(Application $app, Request $request)
    {
        $id = (int) $request->get('id', 0);

        $usersModel = new UsersModel($app);
        $user = $usersModel->getUser($id);
        $token = $app['security']->getToken();
        
        $choiceRole = $usersModel->getRolesList();

        $data = array(
            'id' => $user['id']
        );

        if (count($user)) {
            $form = $app['form.factory']->createBuilder('form', $data)
                ->add(
                    'role_id',
                    'choice',
                    array(
                        'choices' => $choiceRole
                    )
                )
                ->add('save', 'submit')
                ->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                $data['id'] = $app
                    ->escape($data['id']);
                $data['role_id'] = $app
                    ->escape($data['role_id']);

                try {
                    $this->model->changeRole($data);

                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'success',
                            'content' => 'Account edited. This user will have changed role after next log-in'
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            '/user/panel'
                        ),
                        301
                    );
                } catch (\Exception $e) {
                    $errors[] = 'Something went wrong';
                    
                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'danger',
                            'content' => 'Something went wrong. '
                        )
                    );
                    return $app['twig']->render(
                        'errors/500.twig'
                    );
                }
            }

                return $app['twig']->render(
                    'users/role.twig',
                    array(
                        'form' => $form->createView(),
                        'user' => $user
                    )
                );
        } else {
                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'danger',
                        'content' => 'User not found'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/user/view'
                    ),
                    301
                );
        }

    }


    /**
     * Delete user
     *
     * @param Application $app application object
     * @param Request $request request
     *
     * @access public
     * @return mixed Generates page or redirect
     */
    public function deleteAction(Application $app, Request $request)
    {
        $usersModel = new UsersModel($app);
        $idLoggedUser = $usersModel->getIdCurrentUser($app);

        if ($app['security']->isGranted('ROLE_ADMIN')) {
            $usersModel = new UsersModel($app);
            $id = (int) $request->get('id', 0);
        } else {
            $id = $idLoggedUser;
        }

        $user = $usersModel->getUser($id);
        $token = $app['security']->getToken();
        $loggedUser = $token->getUser()->getUsername();
        $currentUser = $this->model->getUserByLogin($loggedUser);

        if (!$app['security']->isGranted('ROLE_ADMIN')) {
            if ((int)$currentUser['id'] !== (int)$id) {
                echo 'You can not delete this account';
                return $app->redirect(
                    $app['url_generator']->generate('/ads/'),
                    301
                );
            }
        }

        $data = array();

        $form = $app['form.factory']->createBuilder('form', $data)
            ->add(
                'id',
                'hidden',
                array(
                    'data' => $id,
                )
            )
            ->add('Yes', 'submit')
            ->add('No', 'submit')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            if ($form->get('No')->isClicked()) {
                $data = $form->getData();

                return $app->redirect(
                    $app['url_generator']->generate(
                        '/'
                    ),
                    301
                );
            } else {
                try {
                    $adsModel = new AdsModel($app);
                    $adsModel->deleteUsersAds($id);
                    $usersModel->deletePhone($id);
                    $usersModel->deleteUser($id);

                    $app['session']->clear();

                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'success',
                            'content' => 'Account deleted'
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            '/'
                        ),
                        301
                    );
                } catch (\Exception $e) {
                    $errors[] = 'Something went wrong';

                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'danger',
                            'content' => 'User not found'
                        )
                    );
                    return $app['twig']->render('404.twig');
                }
            }
        }
        return $app['twig']->render(
            '/users/delete.twig',
            array(
                'form' => $form->createView(),
                $data
            )
        );

    }

    /**
     * View user profile
     *
     * @param Application $app application object
     * @param Request $request request
     *
     * @access public
     * @return mixed Generate page
     */
    public function viewAction(Application $app, Request $request)
    {
        $usersModel = new UsersModel($app);
        $idLoggedUser = $usersModel->getIdCurrentUser($app);

        if ($app['security']->isGranted('ROLE_ADMIN')) {
            $usersModel = new UsersModel($app);
            $id = (int) $request->get('id', 0);
        } else {
            $id = $idLoggedUser;
        }

        $user = $usersModel-> getUser($id);
        $number = $usersModel-> getPhone($id);

        return $app['twig']->render(
            'users/view.twig',
            array(
                'user' => $user,
                'number' => $number['phone_number'],
                'logged' => $idLoggedUser,
            )
        );
    }

    /**
     * Changing user's password
     *
     * @access public
     * @param Application $app application object
     * @param Request $request request
     * @return mixed generate page or redirect
     */
    public function passwordAction(Application $app, Request $request)
    {
        $id = $this->model->getIdCurrentUser($app);
        $user = $this->model->getUserById($id);

        if (count($user)) {
            $data = array(
                'pass' => $user['password'],
            );

            $form = $app['form.factory']->createBuilder('form', $data)
                ->add(
                    'password',
                    'password',
                    array(
                        'constraints' => array(
                            new Assert\NotBlank(),
                            new Assert\Length(
                                array(
                                    'min' => 5,
                                    'minMessage' =>
                                        'Use more than 4 characters',
                                )
                            ),
                            new Assert\Type(
                                array(
                                    'type' => 'string',
                                    'message' => 'Password not correct',
                                )
                            ),
                        )
                    )
                )
                ->add(
                    'confirm_password',
                    'password',
                    array(
                        'constraints' => array(
                            new Assert\NotBlank(),
                            new Assert\Length(
                                array(
                                    'min' => 5,
                                    'minMessage' =>
                                        'Use more than 4 characters',
                                )
                            ),
                            new Assert\Type(
                                array(
                                    'type' => 'string',
                                    'message' => 'Password is not correct',
                                )
                            ),
                        )
                    )
                )
                ->add(
                    'new_password',
                    'password',
                    array(
                        'constraints' => array(
                            new Assert\NotBlank(),
                            new Assert\Length(
                                array(
                                    'min' => 5,
                                    'minMessage' =>
                                        'Use more than 4 characters',
                                )
                            ),
                            new Assert\Type(
                                array(
                                    'type' => 'string',
                                    'message' => 'Password is not correct',
                                )
                            ),
                        )
                    )
                )
                ->add(
                    'confirm_new_password',
                    'password',
                    array(
                        'constraints' => array(
                            new Assert\NotBlank(),
                            new Assert\Length(
                                array(
                                    'min' => 5,
                                    'minMessage' =>
                                        'Use more than 4 characters',
                                )
                            ),
                            new Assert\Type(
                                array(
                                    'type' => 'string',
                                    'message' => 'Password is not correct',
                                )
                            ),
                        )
                    )
                )
                ->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                $oldPassword = $app['security.encoder.digest']
                    ->encodePassword($data['password'], '');

                if ($oldPassword === $user['password']) {
                    if ($data['new_password']===$data['confirm_new_password']
                        && $data['password'] === $data['confirm_password']
                    ) {
                        $data['new_password'] = $app['security.encoder.digest']
                            ->encodePassword($data['new_password'], '');

                        try {
                            $this->model->changePassword($data, $id);

                            $app['session']->getFlashBag()->add(
                                'message',
                                array(
                                    'type' => 'success',
                                    'content' => 'Password is changed'
                                )
                            );
                            return $app->redirect(
                                $app['url_generator']->generate('/user/account'),
                                301
                            );
                        } catch (\Exception $e) {
                            $errors[] = 'Something went wrong';
                        }

                    } else {
                        $app['session']->getFlashBag()->add(
                            'message',
                            array(
                                'type' => 'warning',
                                'content' => 'Passwords are not the same'
                            )
                        );
                        return $app['twig']->render(
                            'users/password.twig',
                            array(
                                'form' => $form->createView()
                            )
                        );
                    }
                } else {
                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'danger',
                            'content' => 'Current password is not correct'
                        )
                    );

                }
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'User not found'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate(
                    '/auth/login'
                ),
                301
            );
        }
        return $app['twig']->render(
            'users/password.twig',
            array(
                'form' => $form->createView(),
                'user' => $user
            )
        );
    }

    /**
     * Rendering proper view for administartor
     * or normal user
     *
     * @access public
     * @param Application $app application object
     * @return mixed Generate pages
     */
    public function accountAction(Application $app, Request $request)
    {
        $id = (int) $request->get('id', 0);
        $usersModel = new UsersModel($app);
        $idLoggedUser = $usersModel->getIdCurrentUser($app);

        try {
            $info = $usersModel->getUser($idLoggedUser);

            $usersModel = new UsersModel($app);
            $pageLimit = 4;
            $page = (int) $request->get('page', 1);

            try {
                $pagesCount = $usersModel->countUsersAdsPages($pageLimit, $idLoggedUser);
                $page = $usersModel->getCurrentPageNumber($page, $pagesCount);
                $ads = $usersModel->getUsersAdsPage($page, $pageLimit, $idLoggedUser);
                $paginator = array('page' => $page, 'pagesCount' => $pagesCount);
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
        } catch (\Exception $e) {
            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Ads not fond'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }
        return $app['twig']->render(
            'users/account.twig',
            array(
                'ads' => $ads,
                'info' => $info,
                'paginator' => $paginator
            )
        );

    }
}
