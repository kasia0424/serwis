<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', E_ALL);

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;
//tłumaczenia
use Symfony\Component\Translation\Loader\YamlFileLoader;

//błędy
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//twig
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => dirname(dirname(__FILE__)) . '/src/views',
));

//flashbacki
$app->register(new Silex\Provider\SessionServiceProvider());

//baza danych
$app->register(
    new Silex\Provider\DoctrineServiceProvider(), 
    array(
        'db.options' => array(
            'driver'    => 'pdo_mysql',
            'host'      => 'localhost',
            'dbname'    => 'dbname',
            'user'      => 'username',
            'password'  => 'password',
            'charset'   => 'utf8',
            'driverOptions' => array(
                1002=>'SET NAMES utf8'
            )                 
        ),
    )
);

//tłumaczenia
$app->register(
    new Silex\Provider\TranslationServiceProvider(), array(
        'locale' => 'pl',
        'locale_fallbacks' => array('pl'),
    )
);

$app['translator'] = $app->share($app->extend('translator', function($translator, $app) {
    $translator->addLoader('yaml', new YamlFileLoader());
    $translator->addResource('yaml', dirname(dirname(__FILE__)) . '/config/locales/pl.yml', 'pl');
    return $translator;
}));

//formularze
$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());




//obsługa błędów
$app->error(
    function (
        \Exception $e, $code
    ) use ($app) {

        if ($e instanceof Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            $code = (string)$e->getStatusCode();
        }

        if ($app['debug']) {
            return;
        }

        // 404.html, or 40x.html, or 4xx.html, or error.html
        $templates = array(
            'errors/'.$code.'.twig',
            'errors/'.substr($code, 0, 2).'x.twig',
            'errors/'.substr($code, 0, 1).'xx.twig',
            'errors/default.twig',
        );

        return new Response(
            $app['twig']->resolveTemplate($templates)->render(
                array('code' => $code)
            ),
            $code
        );

    }
);





//controllery
$app->mount('/ads/', new Controller\AdsController());
$app->mount('/categories/', new Controller\CategoriesController());
//$app->mount('/photos/', new Controller\PhotosController());
$app->mount('auth', new Controller\AuthController());


//old
// $app->get('/hello/{name}', function ($name) use ($app) {
    // return $app['twig']->render('hello.twig', array(
        // 'name' => $name,
    // ));
// });

// $data = array(
    // 0 => array(
        // 'name' => 'John',
        // 'email' => 'john@example.com',
    // ),
    // 1 => array(
        // 'name' => 'Mark',
        // 'email' => 'mark@example.com',
    // ),
// );
// $app->get('/data/{id}', function (Silex\Application $app, $id) use ($data) {
    // $item = isset($data[$id])?$data[$id]:array();
    // return $app['twig']->render(
        // 'data_item.twig', array('item' => $item)
    // );
// });


//fire(b)wall! - przed mount??
$app->register(
    new Silex\Provider\SecurityServiceProvider(),
        array(
            'security.firewalls' => array(
                'unsecured' => array(
                    'anonymous' => true,
                ),
            ),
        )
);

//firewall - z regułami
// $app->register(
    // new Silex\Provider\SecurityServiceProvider(),
    // array(
        // 'security.firewalls' => array(
            // 'admin' => array(
                // 'pattern' => '^.*$',
                // 'form' => array(
                    // 'login_path' => 'auth_login',
                    // 'check_path' => 'auth_login_check',
                    // 'default_target_path'=> '/ads/',
                    // 'username_parameter' => 'loginForm[login]',
                    // 'password_parameter' => 'loginForm[password]',
                // ),
                // 'anonymous' => true,
                // 'logout' => array(
                    // 'logout_path' => 'auth_logout',
                    // 'target_url' => '/ads/index'
                // ),
                // 'users' => $app->share(
                    // function() use ($app)
                    // {
                        // return new Provider\UserProvider($app);
                    // }
                // ),
            // ),
        // ),
        // 'security.access_rules' => array(
            // array('^/auth.+$', 'IS_AUTHENTICATED_ANONYMOUSLY'),
            // array('^/.+$', 'ROLE_ADMIN')
        // ),
        // 'security.role_hierarchy' => array(
            // 'ROLE_ADMIN' => array('ROLE_USER'),
        // ),
    // )
// );

$app->run();
