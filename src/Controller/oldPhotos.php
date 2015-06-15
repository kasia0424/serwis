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
        $adId =$request->get('id', 0);
        //$app['session']->set('ad', array('ad_id' => $adId));
        
        $adsModel = new AdsModel($app);
        $ad = $adsModel->getAd($adId);
        var_dump($adId);
        
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

        $data = array(
            'ad_id' => $adId,
            // 'user_id' =>
        );
        $form = $app['form.factory']
            ->createBuilder(new FilesForm(), $data)->getForm();
         $form->remove('id_file');
        // $form = $app['form.factory']->createBuilder('form', $data)
            // ->add(
                // 'file',
                // 'file',
                // array(
                    // 'label' => 'Choose file',
                    // 'constraints' => array(new Assert\Image())
                // )
            // )
            // // ->add(
                // // 'flag',
                // // 'hidden', array(
                    // // 'data' => $adId
                // // )
            // // )
            // ->add(
                // 'save',
                // 'submit',
                // array('label' => 'Upload file')
            // )
            // ->getForm();
        //$form['ad_id']=$adId;
        var_dump($form);

        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                //try {
                    //$flag= true;
                    $data = $form->getData();
                    var_dump($form);
                    $ad = $app['session']->get('ad');
                    //$adId = $form.a_id;
                    $post = $request->request->get('a_id');
                    $post = $request->request->getForm();
                    
                    //var_dump($post['a_id']);
                    //var_dump($request);die();
                    
                    $files = $request->files->get($form->getName()); //sprawdzanie poprawności danych!
                    $path = dirname(dirname(dirname(__FILE__))).'/web/media';

                    $photosModel = new PhotosModel($app);

                    $originalFilename = $files['file']->getClientOriginalName();

                    $newFilename = $photosModel->createName($originalFilename); //tworzy nową nazwę
                    $files['file']->move($path, $newFilename); //prznoszenie z tymczas. do ostatecznego msc
                    
                    $photosModel->saveFile($newFilename, $data); //zapisywanie
                    
                    //$adsModel = new AdsModel($app);
                    //$adsModel->adPhoto($adId);

                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'success',
                            'content' => 'File successfully uploaded.'
                        )
                    );
                    $flag= true;

                // } catch (Exception $e) {
                    // $app['session']->getFlashBag()->add(
                        // 'message',
                        // array(
                            // 'type' => 'error',
                            // 'content' => 'Can not upload file.'
                        // )
                    // );
                // }

            } else {
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
