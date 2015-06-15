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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        return $builder
        ->add(
            'image',
            'file',
            array(
                'label' => 'Choose file'
            )
        )
        ->add(
            'ad_id',
            'hidden',
            array(
                'data' => $options['data']['ad_id'],
                'label' => 'add_id'
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
        return 'filesForm';
    }
}
