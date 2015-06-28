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
            'text',
            array(
                'attr' => array(
                     'placeholder' => 'Nazwa',
                ),
                'constraints' => array(
                    new Assert\NotBlank(), new Assert\Length(
                        array(
                            'min' => 3,
                            'minMessage' =>'Użyj więcej niż 2 znaków',
                        )
                    )
                )
            )
        )
        ->add(
            'description',
            'textarea',
            array(
                'attr' => array(
                     'placeholder' => 'Opis',
                ),
                'constraints' => array(
                    new Assert\NotBlank(), new Assert\Length(
                        array(
                            'min' => 3,
                            'minMessage' =>'Użyj więcej niż 2 znaków',
                        )
                    )
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
