ComurImageBundle
============

Image upload / crop bundle for Symfony2

This bundle helps you easyly create an image upload / crop field in your forms. You don't need to use any type of generator or there is no other requirements.
It uses bootstrap to make it look well but you can use any other css to customize it.


Installation
------------

1. Add this bundle to your project in composer.json:

    ```json
    {
        "require": {
            "comur/image-bundle": "dev-master",
        }
    }
    ```

2. Add this bundle to your app/AppKernel.php:

    ``` php
    // app/AppKernel.php
    public function registerBundles()
    {
        return array(
            // ...
            new Comur\ImageBundle\ComurImageBundle(),
            // ...
        );
    }
    ```
3. Add this route to your routing.yml:

    ``` yaml
    // app/config/routing.yml
    comur_image:
        resource: "@ComurImageBundle/Resources/config/routing.yml"
        prefix:   /
    ```

That's it !

Configuration
-------------

For now, there is only one parameter (optional) that you can add to your config.yml:

    ``` yaml
    comur_image
        config:
            cropped_image_dir: 'cropped'
    ```

It's used to determine relative directory name to put cropped images (see above).

Usage
-----

1. In simple Symfony forms:

    ``` php

    ```
