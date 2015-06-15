<?php

namespace Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Model\PhotosModel;
use Model\AdsModel;
use Model\UsersModel;
use Form\FilesForm;

class PhotosController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $photosController = $app['controllers_factory'];
        $photosController->match('/', array($this, 'upload'))
            ->bind('/photos/');
        $photosController->match('/upload', array($this, 'upload'))
            ->bind('/photos/upload');
        return $photosController;
    }

    public function upload(Application $app, Request $request)
    {
        $adId =(int)$request->get('id', 0);
        //$app['session']->set('ad', array('ad_id' => $adId));

        $adsModel = new AdsModel($app);
        $ad = $adsModel->getAd($adId);

        $usersModel = new UsersModel($app);
        $idLoggedUser = $usersModel->getIdCurrentUser($app);

        $flag = false;

        if ($flag == false) {
        // if (!$app['security']->isGranted('ROLE_ADMIN')) {
            // if ((int)$ad['user_id'] !== (int)$idLoggedUser) {
                // $app['session']->getFlashBag()->add(
                    // 'message',
                    // array(
                        // 'type' => 'danger',
                        // 'content' => 'This is not your ad - you can not adddd photo to it.'
                    // )
                // );
                // return $app['twig']->render(
                    // 'errors/403.twig'
                // );
            // }
        // }
        }

        $form = $app['form.factory']
            ->createBuilder(new FilesForm(), array('ad_id'=>$adId))->getForm();

        if ($request->isMethod('post')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                try {
                    $data = $form->getData();
                    $files = $request->files->get($form->getName()); //sprawdzanie poprawno?ci danych!
                    $path = dirname(dirname(dirname(__FILE__))).'/web/media';

                    $photosModel = new PhotosModel($app);

                    $originalFilename = $files['image']->getClientOriginalName();

                    $newFilename = $photosModel->createName($originalFilename); //tworzy now? nazw?
                    $files['image']->move($path, $newFilename); //prznoszenie z tymczas. do ostatecznego msc
// var_dump($data);
// var_dump($newFilename);
                    $adId = $data['ad_id'];
                    $photo = $photosModel->getPhoto($adId);
                    
                    if($photo == null) {
                        $photosModel->saveFile($newFilename, $data); //zapisywanie
                    } else {
                        $photosModel->updateFile($newFilename, $data);
                    }
                    
                    
                    //$adsModel = new AdsModel($app);
                    //$adsModel->adPhoto($adId);

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
                            array('id' => $data['ad_id'])),
                        301
                    );
                    $flag= true;

                 } catch (Exception $e) {
                     $app['session']->getFlashBag()->add(
                         'message',
                         array(
                             'type' => 'error',
                             'content' => 'Can not upload file.'
                         )
                     );
                 }

            } else {
                var_dump($form);
                $app['session']->getFlashBag()->add(
                    'message',
                    array(
                        'type' => 'error',
                        'content' => 'Form contains invalid data.'
                    )
                );
            }
        }

        return $app['twig']->render(
            'photos/upload.twig',
            array('form' => $form->createView())
        );
    }


}
