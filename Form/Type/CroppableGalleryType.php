<?php

namespace Comur\ImageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
// use Symfony\Component\Form\FormBuilder;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CroppableGalleryType extends CroppableImageType
{
    protected $galleryDir = null;
    protected $thumbsDir = null;
    protected $isGallery = true;
    protected $galleryThumbSize = null;

    public function getParent()
    {
        return 'collection';
    }

    public function getName()
    {
        return 'comur_gallery';
    }

    public function __construct($galleryDir, $thumbsDir, $galleryThumbSize)
    {
        $this->galleryDir = $galleryDir;
        $this->thumbsDir = $thumbsDir;
        $this->galleryThumbSize = $galleryThumbSize;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {


        parent::setDefaultOptions($resolver);

        $galleryDir = $this->galleryDir;

        $resolver->setNormalizers(array(
            'options' => function(Options $options, $value){
                return array_merge($value, array(
                    'attr' => array('style' => 'padding:0; border: 0; margin: 0; opacity: 0;width: 0; max-width: 0; height: 0; max-height: 0;'),
                    // 'allow_add' => true,
                    // 'allow_delete' => true
                ));
            },
            'allow_add' => function(Options $options, $value){ return true; },
            'allow_delete' => function(Options $options, $value){ return true; }
            
        ));
    //     $uploadConfig = array(
    //         'uploadRoute' => 'comur_api_upload',
    //         'uploadUrl' => null,
    //         'webDir' => null,
    //         'fileExt' => '*.jpg;*.gif;*.png;*.jpeg',
    //         'libraryDir' => null,
    //         'libraryRoute' => 'comur_api_image_library',
    //         'showLibrary' => true
    //     );

    //     $cropConfig = array(
    //         'minWidth' => 1,
    //         'minHeight' => 1,
    //         'aspectRatio' => true,
    //         'cropRoute' => 'comur_api_crop',
    //         'forceResize' => false,
    //         'thumbs' => null
    //     );

    //     $resolver->setDefaults(array(
    //         'uploadConfig' => $uploadConfig,
    //         'cropConfig' => $cropConfig,
    //     ));
        
    //     $resolver->setNormalizers(array(
    //         'uploadConfig' => function(Options $options, $value) use ($uploadConfig){
    //             $config = array_merge($uploadConfig, $value);
    //             if(!isset($config['libraryDir'])){
    //                 $config['libraryDir'] = $config['uploadUrl'];
    //             }
    //             return $config;
    //         },
    //         'cropConfig' => function(Options $options, $value) use($cropConfig){
    //             return array_merge($cropConfig, $value);
    //         }
    //     ));
        
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $uploadConfig = $options['uploadConfig'];
        $cropConfig = $options['cropConfig'];
        // $options['type'] = 'text';

        // var_dump($options);exit;

        $uploadConfig['isGallery'] = true;

        $view->vars['options'] = array('uploadConfig' => $uploadConfig, 'cropConfig' => $cropConfig, 'galleryThumbSize' => $this->galleryThumbSize);
        // $view->vars['options']['attr'] = array('style' => 'opacity: 0;width: 0; max-width: 0; height: 0; max-height: 0;', 'multiple' => true);
        // $view->vars['attr'] = array('style' => 'opacity: 0;width: 0; max-width: 0; height: 0; max-height: 0;', 'multiple' => true);
    }
}