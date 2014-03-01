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

    public function __construct(array $options = array())
    {
        $resolver = new OptionsResolver();
        $this->setDefaultOptions($resolver);
        $this->options = $resolver->resolve($options);
        // var_dump($options, $this->options, $resolver);exit;
    }

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
        // parent::setDefaultOptions($resolver);
        // throw new \Exception();

        $resolver->setDefaults(array(
            // 'mapped' => false,
            'uploadConfig' => function (Options $options, $value) {
                // var_dump($value, 'val');exit;
                return array(
                    'uploadRoute' => isset($value['uploadRoute']) ? $value['uploadRoute'] : 'comur_api_upload',
                    'uploadUrl' => isset($value['uploadUrl']) ? $value['uploadUrl'] : null,
                    'webDir' => isset($value['webDir']) ? $value['webDir'] : null,
                    'fileExt' => isset($value['fileExt']) ? $value['fileExt'] : '*.jpg;*.gif;*.png;*.jpeg',
                    'libraryDir' => isset($value['libraryDir']) ? $value['libraryDir'] : null,
                    'libraryRoute' => isset($value['libraryRoute']) ? $value['libraryRoute'] : 'comur_api_image_library',
                    'showLibrary' => isset($value['showLibrary']) ? $value['showLibrary'] : true,
                );
            },
            'cropConfig' => function (Options $options, $value) {
                return array(
                    'minWidth' => isset($value['minWidth']) ? $value['minWidth'] : 1,
                    'minHeight' => isset($value['minHeight']) ? $value['minHeight'] : 1,
                    'aspectRatio' => isset($value['aspectRatio']) ? $value['aspectRatio'] : true,
                    'cropRoute' => isset($value['cropRoute']) ? $value['cropRoute'] : 'comur_api_crop',
                    'forceResize' => false,
                    'thumbs' => null
                );
            }
        ));

    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $uploadConfig = array_merge($this->options['uploadConfig'], $options['uploadConfig']);
        $cropConfig = array_merge($this->options['cropConfig'], $options['cropConfig']);

        if(!isset($uploadConfig['libraryDir'])){
            $uploadConfig['libraryDir'] = $uploadConfig['uploadUrl'];
        }

        $view->vars['options'] = array('uploadConfig' => $uploadConfig, 'cropConfig' => $cropConfig);
        $view->vars['attr'] = array('style' => 'opacity: 0;width: 0; max-width: 0; height: 0; max-height: 0;');
    }
}