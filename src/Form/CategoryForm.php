<?php
/**
 * Category form.
 *
 * @author Wanda Sipel
 * @link http://wierzba.wzks.uj.edu.pl/~12_sipel/serwis/web/categories/
 * @copyright 2015 EPI
 */

namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class CategoryForm.
 *
 * @category Epi
 * @package Form
 * @extends AbstractType
 * @use Symfony\Component\Form\AbstractType
 * @use Symfony\Component\Form\FormBuilderInterface
 * @use Symfony\Component\OptionsResolver\OptionsResolverInterface
 * @use Symfony\Component\Validator\Constraints as Assert
 */
class CategoryForm extends AbstractType
{
    /**
     * Form builder.
     *
     * @access public
     * @param FormBuilderInterface $builder
     * @param array $options
     *
     * @return FormBuilderInterface
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        return  $builder->add(
            'id',
            'hidden',
            array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Type(array('type' => 'digit'))
                )
            )
        )
        ->add(
            'name',
            'textarea',
            array(
                'constraints' => array(
                    new Assert\NotBlank(),new Assert\Length(
                        array(
                            'min' => 5,
                            'minMessage' =>'Use more than 5 characters',
                        )
                    )
                )
            )
        )
        ->add(
            'description',
            'text',
            array(
                'constraints' => array(
                    new Assert\NotBlank(),
                    new Assert\Length(array('min' => 5))
                )
            )
        );
    }

    /**
     * Gets form name.
     *
     * @access public
     *
     * @return string
     */
    public function getName()
    {
        return 'adForm';
    }
}
