<?php
/**
 * Categories controller.
 *
 * @link http://epi.uj.edu.pl
 * @author epi(at)uj(dot)edu(dot)pl
 * @copyright EPI 2015
 */

namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

use Model\CategoriesModel;
use Form\CategoryForm;
use Model\AdsModel;
use Model\UsersModel;

class CategoriesController implements ControllerProviderInterface
{
    /**
     * Routing settings.
     *
     * @access public
     * @param Silex\Application $app Silex application
     */
    public function connect(Application $app)
    {
        $categoriesController = $app['controllers_factory'];
        $categoriesController->get('/', array($this, 'indexAction'))
            ->bind('/categories/');
        $categoriesController->match('/add', array($this, 'addAction'))
            ->bind('/categories/add');
        $categoriesController->match('/edit/{id}', array($this, 'editAction'))
            ->bind('/categories/edit');
        $categoriesController->match('/delete/{id}', array($this, 'deleteAction'))
            ->bind('/categories/delete');
        $categoriesController->get('/view/{id}', array($this, 'viewAction'))
            ->bind('/categories/view');
        return $categoriesController;
    }

    /**
     * Index action.
     *
     * @access public
     * @param Silex\Application $app Silex application
     * @param Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function indexAction(Application $app, Request $request)
    {
        // $view = array();
        // $categoriesModel = new CategoriesModel($app);

        // try {
            // $view['categories'] = $categoriesModel->getAll();
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
            // ->render('categories/index.twig', $view);
        $usersModel = new UsersModel($app);
        $idLoggedUser = $usersModel->getIdCurrentUser($app);
        
        $pageLimit = 7;
        $page = (int) $request->get('page', 1);
        $categoriesModel = new CategoriesModel($app);
        try {
            $pagesCount = $categoriesModel->countCategoriesPages($pageLimit);
            $page = $categoriesModel->getCurrentPageNumber($page, $pagesCount);
            $categories = $categoriesModel->getCategoriesPage($page, $pageLimit);
            $this->view['paginator']
                = array('page' => $page, 'pagesCount' => $pagesCount);
            $this->view['categories'] = $categories;
        } catch (\Exception $e) {
            $errors[] = 'Something went wrong';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Categories not found'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }
        return $app['twig']->render('categories/index.twig', $this->view);
    }

    /**
     * Add action.
     *
     * @access public
     * @param Silex\Application $app Silex application
     * @param Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function addAction(Application $app, Request $request)
    {
        $data = array(
            'name' => 'Name of category',
            'description' => 'Describe category in few words',
        );


        $form = $app['form.factory']->createBuilder('form', $data)
            ->add(
                'name',
                'text',
                array(
                    'constraints' => array(
                        new Assert\NotBlank(), new Assert\Length(
                            array(
                                'min' => 3
                            )
                        )
                    )
                )
            )
            ->add(
                'description',
                'textarea',
                array(
                    'constraints' => array(
                        new Assert\NotBlank(), new Assert\Length(
                            array(
                                'min' => 3
                            )
                        )
                    )
                )
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $categoriesModel = new CategoriesModel($app);
            $data = $form->getData();

            $categoriesModel->saveCategory($data);

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'success',
                    'content' => 'Category has been added.'
                )
            );
            return $app->redirect(
                $app['url_generator']->generate('/categories/'),
                301
            );
        }

        return $app['twig']->render(
            'categories/add.twig',
            array('form' => $form->createView()
            )
        );
    }

    /**
     * Edit action.
     *
     * @access public
     * @param Silex\Application $app Silex application
     * @param Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function editAction(Application $app, Request $request)
    {
        $categoriesModel = new CategoriesModel($app);
        $id = (int) $request->get('id', 0);
        $category = $categoriesModel->getCategory($id);
        
        // if (!$category) {
            // echo '<span style="background-color: red; color: white; padding: 0.5em;">Category does not exist</span>';
            // return $app->redirect(
                // $app['url_generator']->generate('/categories/'),
                // 301
            // );
        // }

        $data = array(
            'id'=> $id,
            'name' => $category['name'],
            'description' => $category['description'],
        );

        try {
            $form = $app['form.factory']
                ->createBuilder(
                    new CategoryForm(),
                    $category
                )->getForm();
            // $form = $app['form.factory']->createBuilder('form', $category)
                // ->add(
                    // 'id', 'hidden',
                    // array(
                        // 'constraints' => array(
                            // new Assert\NotBlank(),
                            // new Assert\Type(array('type' => 'digit'))
                        // )
                    // )
                // )
                // ->add(
                    // 'title', 'text',
                    // array(
                        // 'constraints' => array(
                            // new Assert\NotBlank(),
                            // new Assert\Length(array('min' => 5))
                        // )
                    // )
                // )
                // ->add(
                    // 'text', 'text',
                    // array(
                        // 'constraints' => array(
                            // new Assert\NotBlank(),
                            // new Assert\Length(array('min' => 5))
                        // )
                    // )
                // )
                // ->getForm();
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $categoriesModel = new CategoriesModel($app);
                $categoriesModel->saveCategory($data);
               
                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'success',
                        'content' => 'Category has been edited.'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate('/categories/'),
                    301
                );

            }
        } catch (\Exception $e) {
            $errors[] = 'Something went wrong';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Category not found'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }
        return $app['twig']->render(
            'categories/edit.twig',
            array(
                'form' => $form->createView(),
                'add' => $add
            )
        );
    }

    /**
     * Delete action.
     *
     * @access public
     * @param Silex\Application $app Silex application
     * @param Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function deleteAction(Application $app, Request $request)
    {
        $id = (int) $request -> get('id', 0);
        $categoriesModel = new CategoriesModel($app);
        
        $restId = '19';
        
        if ((int)$id == 19) {
            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'You can not delete this category. It is a default one.'
                )
            );
            return $app['twig']->render(
                'errors/403.twig'
            );
        }


        try {
            $ads = $categoriesModel->getCategoryAds($id);
            foreach ($ads as $ad) {
                $categoriesModel->restCategory($ad['id']);
            };
            
            $categoriesModel -> deleteCategory($id);
            
            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'success',
                    'content' => 'Category has been deleted. Ads from this category are in Pozostałe category.'
                )
            );
        } catch (\Exception $e) {
            $errors[] = 'Something went wrong';
            $app->abort(404, $app['translator']->trans('Category not found'));

            return $app['twig']->render(
                'errors/404.twig'
            );
        }
        return $app->redirect(
            $app['url_generator']->generate('/categories/'),
            301
        );
    }

    /**
     * View action.
     *
     * @access public
     * @param Silex\Application $app Silex application
     * @param Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function viewAction(Application $app, Request $request)
    {
        $categoriesModel = new CategoriesModel($app);
        $id = (int) $request->get('id', 0);

        try {
            $category = $categoriesModel->getCategory($id);
            //$ads = $categoriesModel->getCategoryAds($id);
            $pageLimit = 3;
            $page = (int) $request->get('page', 1);

            try {
                $pagesCount = $categoriesModel->countCategoriesAdsPages($pageLimit, $id);
                $page = $categoriesModel->getCurrentPageNumber($page, $pagesCount);
                $ads = $categoriesModel->getCategoriesAdsPage($page, $pageLimit, $id);
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
            //$errors[] = 'Something went wrong';
            //$app->abort(404, $app['translator']->trans('Category not found'));
            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Category not fond'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }

        return $app['twig']->render(
            'categories/view.twig',
            array(
                'category' => $category,
                'ads' =>$ads,
                'paginator' =>$paginator,
            )
        );
    }
}