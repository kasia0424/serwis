<?php
/**
 * Categories controller.
 *
 * @link http://wierzba.wzks.uj.edu.pl/~12_sipel/serwis/web/categories/
 * @author Wanda Sipel
 * @copyright EPI 2015
 */

namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

use Model\CategoriesModel;
use Model\AdsModel;
use Model\UsersModel;
use Form\CategoryForm;
use Form\DeleteForm;

/**
 * Class CategoriesController.
 *
 * @package Controller
 * @implements ControllerProviderInterface
 */
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
        $categoriesController->get('/', array($this, 'indexAction'));
        $categoriesController->match('/add', array($this, 'addAction'))
            ->bind('/categories/add');
        $categoriesController->match('/edit/{id}', array($this, 'editAction'))
            ->bind('/categories/edit');
        $categoriesController->match('/delete/{id}', array($this, 'deleteAction'))
            ->bind('/categories/delete');
        $categoriesController->get('/view/{id}', array($this, 'viewAction'));
        $categoriesController->get('/view/{id}/{page}', array($this, 'viewAction'))
                ->value('page', 1)->bind('/categories/view');
        $categoriesController->get('/{page}', array($this, 'indexAction'))
                ->value('page', 1)->bind('/categories/');
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
        try {
            $usersModel = new UsersModel($app);
            $idLoggedUser = $usersModel->getIdCurrentUser($app);
            
            $pageLimit = 7;
            $page = (int) $request->get('page', 1);
            $categoriesModel = new CategoriesModel($app);
        
            $pagesCount = $categoriesModel->countCategoriesPages($pageLimit);
            $page = $categoriesModel->getCurrentPageNumber($page, $pagesCount);
            $categories = $categoriesModel->getCategoriesPage($page, $pageLimit);
            $this->view['paginator']
                = array('page' => $page, 'pagesCount' => $pagesCount);
            $this->view['categories'] = $categories;
        } catch (\Exception $e) {
            $errors[] = 'Coś poszło nie tak';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Kategorie nie znalezione'
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
        try {
            $form = $app['form.factory']
                ->createBuilder(new CategoryForm(), $data)->getForm();
            $form->remove('id');

            $form->handleRequest($request);

            if ($form->isValid()) {
                $categoriesModel = new CategoriesModel($app);
                $data = $form->getData();

                $categoriesModel->saveCategory($data);

                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'success',
                        'content' => 'Kategoria została dodana.'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate('/categories/'),
                    301
                );
            }

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
        try {
            $categoriesModel = new CategoriesModel($app);
            $id = (int) $request->get('id', 0);
            $category = $categoriesModel->getCategory($id);
        } catch (\Exception $e) {
            $errors[] = 'Coś poszło nie tak podczas pobierania danych';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Kategoria nie znaleziona'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }

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
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = $form->getData();
                $categoriesModel = new CategoriesModel($app);
                $categoriesModel->saveCategory($data);
               
                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'success',
                        'content' => 'Kategoria została edytowana.'
                    )
                );
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/categories/view',
                        array('id'=> $id)
                    ),
                    301
                );

            }
        } catch (\Exception $e) {
            $errors[] = 'Coś poszło nie tak podczas przetwarzania danych';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Kategoria nie znaleziona'
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
        try {
            $id = (int) $request -> get('id', 0);
            $categoriesModel = new CategoriesModel($app);
        } catch (\Exception $e) {
            $errors[] = 'Coś poszło nie tak podczas pobierania danych';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Kategoria nie znaleziona'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }
        
        $restId = '19';
        
        if ((int)$id == 19) {
            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Nie możesz usunąć tej kategorii. To domyślna kategoria.'
                )
            );
            return $app['twig']->render(
                'errors/403.twig'
            );
        }

        $data = array();
        try {
            $form = $app['form.factory']
                ->createBuilder(new DeleteForm(), $data)->getForm();
        } catch (\Exception $e) {
            $errors[] = 'Coś poszło nie tak podczas tworzenia formularza';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Kategoria nie znaleziona'
                )
            );
            return $app['twig']->render(
                'errors/404.twig'
            );
        }

        $form->handleRequest($request);
        
        if ($form->isValid()) {
            if ($form->get('Nie')->isClicked()) {
                return $app->redirect(
                    $app['url_generator']->generate(
                        '/categories/'
                    ),
                    301
                );
            } else {
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
                            'content' => 'Kategoria została usunięta.
                            Wszystkie ogłoszenia z niej zostały przeniesione do kategorii Pozostałe.'
                        )
                    );
                    return $app->redirect(
                        $app['url_generator']->generate(
                            '/categories/'
                        ),
                        301
                    );
                } catch (\Exception $e) {
                    $errors[] = 'Coś poszło nie tak podczas przetwarzania danych';

                    return $app['twig']->render(
                        'errors/404.twig'
                    );
                };
            }
        }
        return $app['twig']->render(
            '/categories/delete.twig',
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
     * @param Silex\Application $app Silex application
     * @param Symfony\Component\HttpFoundation\Request $request Request object
     * @return string Output
     */
    public function viewAction(Application $app, Request $request)
    {
        $id = (int) $request->get('id', 0);

        try {
            $categoriesModel = new CategoriesModel($app);
            $category = $categoriesModel->getCategory($id);
            $pageLimit = 3;
            $page = (int) $request->get('page', 1);

            try {
                $pagesCount = $categoriesModel->countCategoriesAdsPages($pageLimit, $id);
                $page = $categoriesModel->getCurrentPageNumber($page, $pagesCount);
                $ads = $categoriesModel->getCategoriesAdsPage($page, $pageLimit, $id);
                $paginator = array('page' => $page, 'pagesCount' => $pagesCount);
                $this->view['ads'] = $ads;
            } catch (\Exception $e) {
                $errors[] = 'Coś poszło nie tak podczas przetwarzania danych';

                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'danger',
                        'content' => 'ogłoszenia nie znalezione'
                    )
                );
                return $app['twig']->render(
                    'errors/404.twig'
                );
            }

        } catch (\Exception $e) {
            $errors[] = 'Coś poszło nie tak';

            $app['session']->getFlashBag()->add(
                'message',
                array(
                    'type' => 'danger',
                    'content' => 'Kategoria nie znaleziona'
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
