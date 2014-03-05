<?php

namespace Comur\ImageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
// use Symfony\Component\Form\FormBuilder;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CroppableImageType extends AbstractType
{

    protected $isGallery = false;
    protected $galleryDir = null;
    protected $thumbsDir = null;

    public function getParent()
    {
        return 'text';
    }

    public function getName()
    {
        return 'comur_image';
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {

        $uploadConfig = array(
            'uploadRoute' => 'comur_api_upload',
            'uploadUrl' => null,
            'webDir' => null,
            'fileExt' => '*.jpg;*.gif;*.png;*.jpeg',
            'libraryDir' => null,
            'libraryRoute' => 'comur_api_image_library',
            'showLibrary' => true
        );

        $cropConfig = array(
            'minWidth' => 1,
            'minHeight' => 1,
            'aspectRatio' => true,
            'cropRoute' => 'comur_api_crop',
            'forceResize' => false,
            'thumbs' => null
        );

        $resolver->setDefaults(array(
            'uploadConfig' => $uploadConfig,
            'cropConfig' => $cropConfig,
        ));
        
        $isGallery = $this->isGallery;
        $galleryDir = $this->galleryDir;

        $resolver->setNormalizers(array(
            'uploadConfig' => function(Options $options, $value) use ($uploadConfig, $isGallery, $galleryDir){
                $config = array_merge($uploadConfig, $value);

                if($isGallery){
                    $config['uploadUrl'] = $config['uploadUrl'].'/'.$galleryDir;
                    $config['webDir'] = $config['webDir'].'/'.$galleryDir;
                }

                if(!isset($config['libraryDir'])){
                    $config['libraryDir'] = $config['uploadUrl'];
                }
                return $config;
            },
            'cropConfig' => function(Options $options, $value) use($cropConfig){
                return array_merge($cropConfig, $value);
            }
        ));
        
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $uploadConfig = $options['uploadConfig'];
        $cropConfig = $options['cropConfig'];

        $fieldImage = null;
        if(isset($cropConfig['thumbs']) && count($thumbs = $cropConfig['thumbs']) > 0)
        {
            foreach ($thumbs as $thumb) {
                if(isset($thumb['useAsFieldImage']) && $thumb['useAsFieldImage'])
                {
                    $fieldImage = $thumb;
                }
            }
        }

        $view->vars['options'] = array('uploadConfig' => $uploadConfig, 'cropConfig' => $cropConfig, 'fieldImage' => $fieldImage);
        $view->vars['attr'] = array('style' => 'opacity: 0;width: 0; max-width: 0; height: 0; max-height: 0;');
    }
}