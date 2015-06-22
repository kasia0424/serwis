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
//use Symfony\Component\OptionsResolver\OptionsResolverInterface;
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
        // $categoriesModel = new CategoriesModel($app);
        // $choiceCategory = $categoriesModel->getCategoriesList();

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
                         'placeholder' => 'Title',
                    ),
                    'label' => false,
                    'constraints' => array(
                        new Assert\NotBlank(), new Assert\Length(
                            array(
                                'min' => 3,
                                'max' => 30,
                                'minMessage' =>'Use more than 2 characters',
                                'maxMessage' =>'Use less than 30 characters',

                            )
                        ),
                        new Assert\Regex(
                            array(
                                'pattern' => "/[a-zA-z]{3,}/",
                                //'match' =>   true,
                                'message' => 'It\'s your ad\'s title - use at least 3 letters in it.',
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
                         'placeholder' => 'Content of your ad',
                    ),
                    'label' => false,
                    'constraints' => array(
                        new Assert\NotBlank(),new Assert\Length(
                            array(
                                'min' => 5,
                                'minMessage' =>'Use more than 5 characters',

                            )
                        ),
                        new Assert\Regex(
                            array(
                                'pattern' => "/[a-zA-z]{3,}/",
                                'message' => 'It\'s your ad - use at least 3 letters in it.',
                            )
                        )
                    )
                )
            )
            ->add(
                'category_id',
                'choice',
                array(
                    'placeholder' => 'Choose category',
                    'choices' => $options['data']['choiceCategory']
                )
            );
            // ->add(
                // 'user_id',
                // 'hidden',
                // array(
                    // 'constraints' => array(
                        // new Assert\NotBlank(),
                        // new Assert\Type(array('type' => 'digit'))
                    // )
                // )
            // );

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
