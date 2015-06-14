<?php
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

// use Model\CategoriesModel; 

class FilesForm extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        return  $builder
        ->add(
            'file',
            'file',
            array(
                'label' => 'Choose file',
                'constraints' => array(new Assert\Image())
            )
        )
        ->add(
            'flag',
            'hidden', array(
                'data' => $adId
            )
        );
        // ->add('save', 'submit', array('label' => 'Upload file'))
        // ->getForm();
    }

    public function getName()
    {
        return 'filesForm';
    }
}
