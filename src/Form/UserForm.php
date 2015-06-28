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
                                    'Użyj więcej niż 4 znaków',
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
                    'invalid_message' => 'Wprowadzone hasła muszą być takie same.',
                    'options' => array('attr' => array('class' => 'password-field')),
                    'required' => true,
                    'first_options'  => array(
                        'label' => 'Hasło',
                        'attr' => array('placeholder' => 'Użyj więcej niż 4 znaków')
                    ),
                    'second_options' => array(
                        'label' => 'Powtórz hasło',
                        'attr' => array('placeholder' => 'Użyj więcej niż 4 znaków')
                    ),
                    'constraints' => array(
                        new Assert\NotBlank(),
                        new Assert\Length(
                            array(
                                'min' => 5,
                                'minMessage' =>
                                    'Użyj więcej niż 4 znaków',
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
                                    'Użyj dokładnie 10 cyfr i nie więcej niż 2 spacje',
                                'maxMessage' =>
                                    'Użyj dokładnie 10 cyfr i nie więcej niż 2 spacje',
                            )
                        ),
                        new Assert\Regex(
                            array(
                                'pattern' => "/^\(?([0-9]{3})\)?([ .-]?)([0-9]{3})([ .-]?)([0-9]{4})$/",
                                'message' => 'Wpisz cyfry - format: xxx xxx xxxx',
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
