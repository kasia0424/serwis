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
use Form\DeleteForm;
use Form\UserForm;

/**
 * Class UsersController.
 *
 * @package Controller
 * @implements ControllerProviderInterface
 */
class UsersController implements ControllerProviderInterface
{
    /**
     * UsersModel object.
     *
     * @var $model
     * $access protected
     */
    protected $model;


    /**
     * Gets current user
     *
     * @access protected
     * @param Silex\Application $app Silex application
     * return array Result
     */
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
        $usersController->get('/', array($this, 'accountAction'));
        $usersController->match('/add/', array($this, 'addAction'))
            ->bind('/user/add');
        $usersController->match('/delete/{id}', array($this, 'deleteAction'))
            ->bind('/user/delete');
        $usersController->get('/view/{id}', array($this, 'viewAction'))
            ->bind('/user/view');
        $usersController->match('/edit/{id}', array($this, 'passwordAction'))
            ->bind('/user/edit');
        $usersController->match('/number/', array($this, 'numberAction'))
            ->bind('/user/number');
         $usersController->match('/role/{id}', array($this, 'roleAction'))
            ->bind('/user/role');
        $usersController->match('/panel/', array($this, 'indexAction'));
        $usersController->get('/panel/{page}', array($this, 'indexAction'))
                ->value('page', 1)->bind('/user/panel');
        $usersController->get('/{page}', array($this, 'accountAction'))
                ->value('page', 1)->bind('/user/account');
        
        return $usersController;
    }

    /**
     * View list of users
     *
     * @param Application $app
     * @param  Symfony\Component\HttpFoundation\Request $request Request object
     *
     * @access public
     * @return mixed generates page
     */
    public function indexAction(Application $app, Request $request)
    {
        $pageLimit = 4;
        $page = (int) $request->get('page', 1);

        try {
            $usersModel = new UsersModel($app);
            $adsTab = $usersModel->countUserAds();
            
            $pagesCount = $usersModel->countUsersPages($pageLimit);
            $page = $usersModel->getCurrentPageNumber($page, $pagesCount);
            $users = $usersModel->getUsersPage($page, $pageLimit);
            $paginator = array('page' => $page, 'pagesCount' => $pagesCount);
        } catch (\Exception $e) {
            $errors[] = 'Coś poszło nie tak';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Nie znaleziono użytkowników'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }

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


        try {
            $form = $app['form.factory']
                ->createBuilder(new UserForm(), $data)->getForm();
            $form
                ->remove('id');

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
            try {
                $data = $form->getData();

                $data['login'] = $app
                    ->escape($data['login']);
                $data['password'] = $app
                    ->escape($data['password']);
                $data['confirm_password'] = $app
                    ->escape($data['confirm_password']);
                $data['phone_number'] = $app
                        ->escape($data['phone_number']);


                $password = $app['security.encoder.digest']
                    ->encodePassword("{$data['password']}", '');

                $checkLogin = $this->model->getUserByLogin(
                    $data['login']
                );
            } catch (\Exception $e) {
                $errors[] = 'Coś poszło nie tak podczas pobierania danych';

                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'danger',
                        'content' => 'Coś poszło nie tak podczas pobierania danych'
                    )
                );
                return $app['twig']->render(
                    'errors/404.twig'
                );
            }
            if (!$checkLogin === $checkLogin || !$checkLogin) {
                try {
                    $data = $form->getData();

                    $this->model->addUser(
                        $data,
                        $password
                    );
                    $last = $this->model->getLastUser();
                    $this->model->addDetails($data, $last['id']);

                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'success',
                            'content' => 'Konto zostało
                             stworzone. Możesz się zalogować.'
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
                            'content' => 'Coś poszło nie tak. Konto nie zostało utworzone'
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
                        'content' => 'Login zajęty.'
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
        try {
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
                    echo 'Nie możesz edytować tego konta';
                    redirect($app['url_generator']->generate('/ads/'), 301);
                }
            }
        } catch (\Exception $e) {
            $errors[] = 'Coś poszło nie tak podczas pobierania danych';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Coś poszło nie tak podczas pobierania danych'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }

            $data = array(
                'id' => $user['id'],
                'login' => $user['login'],
                'password' => '',
                'confirm_password' => '',
            );

        if (count($user)) {
            $form = $app['form.factory']
                ->createBuilder(new UserForm(), $data)->getForm();
            $form
                ->add('save', 'submit');

            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    $data = $form->getData();

                    $data['login'] = $app
                        ->escape($data['login']);
                    $data['password'] = $app
                        ->escape($data['password']);
                    $data['confirm_password'] = $app
                        ->escape($data['confirm_password']);


                    $password = $app['security.encoder.digest']
                        ->encodePassword("{$data['password']}", '');

                    $checkLogin = $this->model
                        ->getUserByLogin(
                            $data['login']
                        );
                } catch (\Exception $e) {
                    $errors[] = 'Coś poszło nie tak podczas pobierania danych';

                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'danger',
                            'content' => 'Coś poszło nie tak podczas pobierania danych'
                        )
                    );
                    return $app['twig']->render(
                        'errors/404.twig'
                    );
                }
                if ($data['login'] === $checkLogin ||
                    !$checkLogin ||
                    (int)$user['id'] ===(int)$checkLogin['id']) {
                    try {
                        $this->model->saveUser($data, $password);

                        $app['session']->getFlashBag()->add(
                            'message',
                            array(
                                'type' => 'success',
                                'content' => 'Konto zostało edytowane.'
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
                                'content' => 'Coś poszło nie tak. '
                            )
                        );
                        return $app['twig']->render(
                            'errors/500.twig'
                        );
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
                    'content' => 'Nie znaleziono użytkownika'
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
        try {
            $id = $this->model->getIdCurrentUser($app);

            $usersModel = new UsersModel($app);
            $user = $usersModel->getUser($id);
            $token = $app['security']->getToken();
            
            $phone = $usersModel->getPhone($id);

            $data = array(
                'id' => $user['id']
            );
        } catch (\Exception $e) {
            $errors[] = 'Coś poszło nie tak podczas pobierania danych';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Coś poszło nie tak podczas pobierania danych'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }

        if (count($user)) {
            $form = $app['form.factory']
                ->createBuilder(new UserForm(), $data)->getForm();
            $form
                ->remove('login')
                ->remove('password')
                ->add('Zapisz', 'submit');

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
                            'content' => 'Konto zostało edytowane.'
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            '/user/account'
                        ),
                        301
                    );
                } catch (\Exception $e) {
                    $errors[] = 'Coś poszło nie tak';
                    
                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'danger',
                            'content' => 'Coś poszło nie tak. '
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
                    'content' => 'Użytkownik nie został znaleziony'
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
        try {
            $id = (int) $request->get('id', 0);

            $usersModel = new UsersModel($app);
            $user = $usersModel->getUser($id);
            $token = $app['security']->getToken();
            
            $choiceRole = $usersModel->getRolesList();
        } catch (\Exception $e) {
            $errors[] = 'Coś poszło nie tak podczas pobierania danych';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Coś poszło nie tak podczas pobierania danych'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }

        $data = array(
            'id' => $user['id'],
            'role_id' => $user['role_id'],
            'old_role' => $user['role_id']
        );

        if (count($user)) {
            $form = $app['form.factory']->createBuilder('form', $data)
                ->add(
                    'role_id',
                    'choice',
                    array(
                        'label' => 'Kategoria',
                        'choices' => $choiceRole
                    )
                )
                ->add('Zapisz', 'submit')
                ->getForm();

            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();

                $data['id'] = $app
                    ->escape($data['id']);
                $data['role_id'] = $app
                    ->escape($data['role_id']);
                $data['old_role'] = $app
                    ->escape($data['old_role']);

                try {
                    if ($data['old_role'] == 1 && $data['role_id'] == 2) {
                        $admin = $usersModel->countAdmins();

                        if ($admin['admin'] == 1) {
                            $app['session']->getFlashBag()->add(
                                'message',
                                array(
                                    'type' => 'danger',
                                    'content' => 'Nie możesz zmienić roli tego użytkownika. To ostatni admin.'
                                )
                            );
                            return $app->redirect(
                                $app['url_generator']->generate(
                                    '/user/panel'
                                )
                            );
                        }
                    }
                    $this->model->changeRole($data);

                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'success',
                            'content' => 'Konto zostało edytowane.
                            Nowe uprawnienia będą dostępne po ponownym logowaniu'
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            '/user/panel'
                        ),
                        301
                    );
                } catch (\Exception $e) {
                    $errors[] = 'Coś poszło nie tak';
                    
                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'danger',
                            'content' => 'Coś poszło nie tak. '
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
                        'content' => 'Nie znaleziono użytkownika'
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
        try {
            $usersModel = new UsersModel($app);
            $idLoggedUser = $usersModel->getIdCurrentUser($app);

            if ($app['security']->isGranted('ROLE_ADMIN')) {
                $usersModel = new UsersModel($app);
                $id = (int) $request->get('id', 0);
            } else {
                $id = $idLoggedUser;
                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'danger',
                        'content' => 'Usuwasz swoje konto.'
                    )
                );
            }

            $user = $usersModel->getUser($id);
            $token = $app['security']->getToken();
            $loggedUser = $token->getUser()->getUsername();
            $currentUser = $this->model->getUserByLogin($loggedUser);
            
            $delUser = $usersModel->getUser($id);
            if ($delUser['role'] == 'ROLE_ADMIN') {
                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'danger',
                        'content' => 'Nie możesz usunąć konta dmina. Przekaż je komuś.'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate('/user/panel'),
                    301
                );
            }
            if (!$app['security']->isGranted('ROLE_ADMIN')) {
                if ((int)$currentUser['id'] !== (int)$id) {
                    echo 'Nie możesz usunąć tego konta';
                    return $app->redirect(
                        $app['url_generator']->generate('/ads/'),
                        301
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

        $data = array();

        $form = $app['form.factory']->createBuilder('form', $data)
            ->add(
                'id',
                'hidden',
                array(
                    'data' => $id,
                )
            )
            ->add('Tak', 'submit')
            ->add('Nie', 'submit')
            ->getForm();

        $form->handleRequest($request);

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
                    $adsModel = new AdsModel($app);
                    $adsModel->deleteUsersAds($id);
                    $usersModel->deletePhone($id);
                    $usersModel->deleteUser($id);

                    $app['session']->clear();

                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'success',
                            'content' => 'Konto zostało usunięte.'
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            '/'
                        ),
                        301
                    );
                } catch (\Exception $e) {
                    $errors[] = 'Coś poszło nie tak';

                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'danger',
                            'content' => 'Użytkownik nie znaleziony'
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
        try {
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
        } catch (\Exception $e) {
            $errors[] = 'Coś poszło nie tak';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Coś poszło nie tak'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }

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
        try {
            $id = $this->model->getIdCurrentUser($app);
            $user = $this->model->getUserById($id);
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

        if (count($user)) {
            $data = array(
                'pass' => $user['password'],
            );

            $form = $app['form.factory']->createBuilder('form', $data)
                ->add(
                    'password',
                    'repeated',
                    array(
                        'type' => 'password',
                        'invalid_message' => 'Wprowadzone hasła muszą być takie same.',
                        'options' => array('attr' => array('class' => 'password-field')),
                        'required' => true,
                        'first_options'  => array('label' => 'Hasło'),
                        'second_options' => array('label' => 'Powtórz hasło'),
                        'constraints' => array(
                            new Assert\NotBlank(),
                            new Assert\Length(
                                array(
                                    'min' => 5,
                                    'minMessage' =>
                                        'Użyj więcej niż 4 znaków',
                                )
                            )
                        )
                    )
                )
                ->add(
                    'new_password',
                    'repeated',
                    array(
                        'type' => 'password',
                        'invalid_message' => 'Wprowadzone hasła muszą być takie same.',
                        'options' => array('attr' => array('class' => 'password-field')),
                        'required' => true,
                        'first_options'  => array(
                            'label' => 'Nowe hasło',
                            'attr' => array('placeholder' => 'Użyj więcej niż 4 znaków')
                        ),
                        'second_options' => array(
                            'label' => 'Powtórz nowe hasło',
                            'attr' => array('placeholder' => 'Użyj więcej niż 4 znaków')
                        ),
                        'constraints' => array(
                            new Assert\NotBlank(),
                            new Assert\Length(
                                array(
                                    'min' => 5,
                                    'minMessage' =>
                                        'Użyj więcej niż 4 znaków',
                                )
                            )
                        )
                    )
                )
                ->getForm();
            try {
                $form->handleRequest($request);

                if ($form->isValid()) {
                    $data = $form->getData();

                    $oldPassword = $app['security.encoder.digest']
                        ->encodePassword($data['password'], '');

                    if ($oldPassword === $user['password']) {
                        $data['new_password'] = $app['security.encoder.digest']
                            ->encodePassword($data['new_password'], '');

                        try {
                            $this->model->changePassword($data, $id);

                            $app['session']->getFlashBag()->add(
                                'message',
                                array(
                                    'type' => 'success',
                                    'content' => 'Hasło zostało zmienione. Następnym razem użyj nowego.'
                                )
                            );
                            return $app->redirect(
                                $app['url_generator']->generate('/user/account'),
                                301
                            );
                        } catch (\Exception $e) {
                            $errors[] = 'Coś poszło nie tak podczas pobierania danych';

                            $app['session']->getFlashBag()->add(
                                'message',
                                array(
                                    'type' => 'danger',
                                    'content' => 'Coś poszło nie tak podczas pobierania danych'
                                )
                            );
                            return $app['twig']->render(
                                'errors/404.twig'
                            );
                        }
                    } else {
                        $app['session']->getFlashBag()->add(
                            'message',
                            array(
                                'type' => 'danger',
                                'content' => 'Bieżące hasło jest niepoprawne'
                            )
                        );

                    }
                }
            } catch (\Exception $e) {
                $errors[] = 'SCoś poszło nie tak podczas pobierania danych';

                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'danger',
                        'content' => 'Coś poszło nie tak podczas pobierania danych'
                    )
                );
                return $app['twig']->render(
                    'errors/404.twig'
                );
            }
        } else {
            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Użytkownik nie znaleziony'
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
     * @param  Symfony\Component\HttpFoundation\Request $request Request object
     * @return mixed Generate pages
     */
    public function accountAction(Application $app, Request $request)
    {
        try {
            $id = (int) $request->get('id', 0);
            $usersModel = new UsersModel($app);
            $idLoggedUser = $usersModel->getIdCurrentUser($app);
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
            $info = $usersModel->getUser($idLoggedUser);

            $pageLimit = 4;
            $page = (int) $request->get('page', 1);

            try {
                $pagesCount = $usersModel->countUsersAdsPages($pageLimit, $idLoggedUser);
                $page = $usersModel->getCurrentPageNumber($page, $pagesCount);
                $ads = $usersModel->getUsersAdsPage($page, $pageLimit, $idLoggedUser);
                $paginator = array('page' => $page, 'pagesCount' => $pagesCount);
                $this->view['ads'] = $ads;
            } catch (\Exception $e) {
                $errors[] = 'Coś poszło nie tak';

                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'danger',
                        'content' => 'Nie znaleziono ogłoszeń'
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
                    'content' => 'Nie znaleziono ogłoszeń'
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
