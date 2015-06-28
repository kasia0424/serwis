<?php
/**
 * File form.
 *
 * @author Wanda Sipel
 * @link http://wierzba.wzks.uj.edu.pl/~12_sipel/serwis/web/photos/
 * @copyright 2015 EPI
 */
namespace Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;

use Model\AdsModel;

/**
 * Class FilesForm
 *
 * @category Epi
 * @package Form
 * @extends AbstractType
 * @use Symfony\Component\Form\AbstractType
 * @use Symfony\Component\Form\FormBuilderInterface
 * @use Symfony\Component\OptionsResolver\OptionsResolverInterface
 * @use Symfony\Component\Validator\Constraints as Assert
 */
class FilesForm extends AbstractType
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
        return $builder
        ->add(
            'image',
            'file',
            array(
                'label' => 'Wybierz plik',
                'constraints' => array(
                    new Assert\Image(
                        array(
                            'mimeTypes' => array(
                                "image/jpg",
                                "image/jpeg",
                                "image/gif",
                                "image/png"
                            ),
                            'mimeTypesMessage' => "Zdjęcie powinno byc w jednym z formatów: JPG, GIF or PNG."
                        )
                    )
                )
            )
        )
        ->add(
            'ad_id',
            'hidden',
            array(
                'data' => $options['data']['ad_id'],
                'label' => 'add_id'
            )
        )
        ->add('save', 'submit', array('label' => 'Prześlij plik'));
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
        return 'filesForm';
    }
}
