<?php
/**
 * User form.
 *
 * @author Wanda Sipel
 * @link http://wierzba.wzks.uj.edu.pl/~12_sipel/serwis/web/
 * @copyright 2015 EPI
 */

namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class UserForm.
 *
 * @category Epi
 * @package Form
 * @extends AbstractType
 * @use Symfony\Component\Form\AbstractType
 * @use Symfony\Component\Form\FormBuilderInterface
 * @use Symfony\Component\OptionsResolver\OptionsResolverInterface
 * @use Symfony\Component\Validator\Constraints as Assert
 */
class UserForm extends AbstractType
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
        return  $builder
            ->add(
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
                'login',
                'text',
                array(
                    'constraints' => array(
                        new Assert\NotBlank(), new Assert\Length(
                            array(
                                'min' => 5,
                                'minMessage' =>
                                    'Use more than 4 characters',
                            )
                        )
                    )
                )
            )
            ->add(
                'password',
                'repeated',
                array(
                    'type' => 'password',
                    'invalid_message' => 'The password fields must match.',
                    'options' => array('attr' => array('class' => 'password-field')),
                    'required' => true,
                    'first_options'  => array(
                        'label' => 'Password',
                        'attr' => array('placeholder' => 'More than 4 characters')
                    ),
                    'second_options' => array(
                        'label' => 'Repeat password',
                        'attr' => array('placeholder' => 'More than 4 characters')
                    ),
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Length(
                            array(
                                'min' => 5,
                                'minMessage' =>
                                    'Use more than 4 characters',
                            )
                        )
                    )
                )
            )
            ->add(
                'phone_number',
                'text',
                array(
                    'attr' => array(
                         'placeholder' => 'Format: xxx xxx xxxx',
                    ),
                    'constraints' => array(
                        new Assert\NotBlank(), new Assert\Length(
                            array(
                                'min' => 10,
                                'max' => 12,
                                'minMessage' =>
                                    'Use exactelty 10 numbers and not more than 2 spaces',
                                'maxMessage' =>
                                    'Use exactelty 10 numbers and not more than 2 spaces',
                            )
                        ),
                        new Assert\Regex(
                            array(
                                'pattern' => "/^\(?([0-9]{3})\)?([ .-]?)([0-9]{3})([ .-]?)([0-9]{4})$/",
                                'message' => 'Use only numbers - format: xxx xxx xxxx',
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
