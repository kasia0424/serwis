<?php
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Model\AdsModel; 

class FilesForm extends AbstractType
{

    // public function buildForm(FormBuilderInterface $builder, array $options)
    // {
        // return  $builder
        // ->add(
            // 'file',
            // 'file',
            // array(
                // 'label' => 'Choose file',
                // 'constraints' => array(new Assert\Image())
            // )
        // );
        // // ->add(
            // // 'flag',
            // // 'hidden', array(
                // // 'data' => $adId
            // // )
        // // );
        // // ->add('save', 'submit', array('label' => 'Upload file'))
        // // ->getForm();
    // }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        return $builder
        ->add(
            'image',
            'file',
            array(
                'label' => 'Choose file'
                //'constraints' => array(new Assert\Image())
            )
        )
        ->add(
            'ad_id',
            'hidden',
            array(
                'data' => $options['data']['ad_id'],
                'label' => 'add_id'
            )
        );}

    public function getName()
    {
        return 'filesForm';
    }
}
