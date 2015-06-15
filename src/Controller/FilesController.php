<?php
/**
 * Files controller.
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
use Model\FilesModel;

class FilesController implements ControllerProviderInterface
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
        $filesController = $app['controllers_factory'];
        $filesController->match('/', array($this, 'upload'))
            ->bind('/files/');
        $filesController->match('/upload', array($this, 'upload'))
            ->bind('/files/upload');
        return $filesController;
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
        $form = $app['form.factory']->createBuilder('form')
            ->add(
                'file',
                'file',
                array(
                    'label' => 'Choose file',
                    'constraints' => array(new Assert\Image())
                )
            )
            ->add(
                'save',
                'submit',
                array('label' => 'Upload file')
            )
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->bind($request); //przekazywanie do formularza

            if ($form->isValid()) {
                try {
                    $files = $request->files->get($form->getName()); //sprawdzanie poprawnoœci danych!
                    $path = dirname(dirname(dirname(__FILE__))).'/web/media';

                    $filesModel = new FilesModel($app);

                    $originalFilename = $files['file']->getClientOriginalName();

                    $newFilename = $filesModel->createName($originalFilename); //tworzy now¹ nazwê
                    $files['file']->move($path, $newFilename); //prznoszenie z tymczas. do ostatecznego msc
                    $filesModel->saveFile($newFilename); //zapisywanie

                    $app['session']->getFlashBag()->add(
                        'message',
                        array(
                            'type' => 'success',
                            'content' => 'File successfully uploaded.'
                        )
                    );

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
            'files/upload.twig',
            array('form' => $form->createView())
        );
    }
}
