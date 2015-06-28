<?php
/**
 * Ad form.
 *
 * @author Wanda Sipel
 * @link http://wierzba.wzks.uj.edu.pl/~12_sipel/serwis/web/ads/
 * @copyright 2015 EPI
 */

namespace Form;

use Silex\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Model\CategoriesModel;

/**
 * Class AdForm.
 *
 * @category Epi
 * @package Form
 * @extends AbstractType
 * @use Symfony\Component\Form\AbstractType
 * @use Symfony\Component\Form\FormBuilderInterface
 * @use Symfony\Component\OptionsResolver\OptionsResolverInterface
 * @use Symfony\Component\Validator\Constraints as Assert
 */
class AdForm extends AbstractType
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
                'title',
                'text',
                array(
                    'attr' => array(
                         'placeholder' => 'Tytuł',
                    ),
                    'label' => false,
                    'constraints' => array(
                        new Assert\NotBlank(), new Assert\Length(
                            array(
                                'min' => 3,
                                'max' => 30,
                                'minMessage' =>'Użyj przynajmniej 3 znaków w tytule',
                                'maxMessage' =>'Nie możesz użyć więcej niż 30 znaków w tytule',

                            )
                        ),
                        new Assert\Regex(
                            array(
                                'pattern' => "/([a-zA-z\s]*){3,}/",
                                'message' => 'To tytuł twojego ogłoszenia - uzyj przynajmniej 3 litery w nim.',
                            )
                        )
                    )
                )
            )
            ->add(
                'text',
                'textarea',
                array(
                    'attr' => array(
                         'placeholder' => 'Treść ogłoszenia',
                    ),
                    'label' => false,
                    'constraints' => array(
                        new Assert\NotBlank(),new Assert\Length(
                            array(
                                'min' => 5,
                                'minMessage' =>'Użyj więcej niż 4 znaków w treści ogłoszenia
                                (białe znaki z końca są usuwane)',

                            )
                        ),
                        new Assert\Regex(
                            array(
                                'pattern' => "/[a-zA-z\s]{3,}/",
                                'message' => 'To treść twojego ogłoszenia - użyj przynajmniej 3 liter',
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
