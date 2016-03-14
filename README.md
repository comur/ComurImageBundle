Use branch master for compatibility with Syfony 2
------------
Use branch 1.3 for compatibility with Symfony 3
------------
Use 0.X releases for compatibility with bootstrap 2.x.
------------
Compatibility with bootstrap 2.X is no more maintained
------------

ComurImageBundle
============

Image upload / crop bundle for Symfony2

This bundle helps you easily create an image upload / crop field in your forms. You don't need to use any type of generator or there is no other requirements.
It uses bootstrap to make it look well but you can use any other css to customize it.

It uses beautiful [Jquery File Upload](http://blueimp.github.io/jQuery-File-Upload/) to upload files (original UploadHandler has been modified to add namespace and a new config parameter to generate random filenames) and [JCrop](http://deepliquid.com/content/Jcrop.html) to let you crop uploaded images.

**New Since Version 0.2.3 !! you can also save original file in a separate field** See saveOriginal parameter below.

**New Since Version 0.2.0 !! you can also create sortable & croppable gallery widgets** without any specific configuration. It only needs an array typed property in your entity (and a text column in your database). See below for examples, screenshots and how to use it.

Screen shots
------------

Here are some screen shots since i didn't have time to put a demo yet:

###Simple Image widget###

![alt tag](http://canomur.com/comur-image/images/image_widget_ss1.png)

###Gallery widget###

![alt tag](http://canomur.com/comur-image/images/gallery_widget_ss1.png)

###Upload image screen###

![alt tag](http://canomur.com/comur-image/images/upload_image_ss1.png)

###Select image from library screen###

![alt tag](http://canomur.com/comur-image/images/select_image_ss1.png)

###Crop image screen###

![alt tag](http://canomur.com/comur-image/images/crop_image_ss1.png)

###Change gallery image order screen###

![alt tag](http://canomur.com/comur-image/images/order_image_ss1.png)

Installation
------------

1. Add this bundle to your project in composer.json:

	```
    {
        "require": {
            "comur/image-bundle": "1.0.*@dev",
        }
    }
    ```

2. Register FOSJsRouting and this bundle to your app/AppKernel.php:

    ```
    // app/AppKernel.php
    public function registerBundles()
    {
        return array(
            // ...
            new FOS\JsRoutingBundle\FOSJsRoutingBundle(),
            new JMS\TranslationBundle\JMSTranslationBundle(),
            new Comur\ImageBundle\ComurImageBundle(),
            // ...
        );
    }
    ```
3. Add this route to your routing.yml:

    ```
    # app/config/routing.yml
    fos_js_routing:
    	resource: "@FOSJsRoutingBundle/Resources/config/routing/routing.xml"
    	
    comur_image:
        resource: "@ComurImageBundle/Resources/config/routing.yml"
        prefix:   /
    ```
 
4. Add Modal template after body tag of your layout:
	
	```
	<body>
	{% include "ComurImageBundle:Form:croppable_image_modal.html.twig"%}
	…
	</body>

	```

**Note:** This template includes many script and styles including jquery and bootstrap. You can use following parameters to avoid jquery and/or bootstrap being included:

```
{% include "ComurImageBundle:Form:croppable_image_modal.html.twig" with {'include_jquery': false, 'include_bootstrap': false} %}
```
	
5. Do not forget to put [FOSJSRoutingBundle](https://github.com/FriendsOfSymfony/FOSJsRoutingBundle) script in your <head>:

```
	<script src="{{ asset('bundles/fosjsrouting/js/router.js') }}"></script>
	<script src="{{ path('fos_js_routing_js', {"callback": "fos.Router.setData"}) }}"></script>
```

That's it !

Configuration
-------------
<br/>
**All parameters are optional:**

	comur_image:
		config:
			cropped_image_dir: 'cropped'
			thumbs_dir: 'thumbnails'
			media_lib_thumb_size: 150
			web_dirname: 'web'
			translation_domain: 'ComurImageBundle'
			gallery_thumb_size: 150
			gallery_dir: 'gallery'

###cropped_image_dir###

It's used to determine relative directory name to put cropped images (see above).

**Default value:** 'cropped'

###thumbs_dir###

It's used to determine relative directory name to put thumbnails (see above).

**Default value:** 'thumbnails'

###media_lib_thumb_size###

It's used to determine thumbnails size in pixels (squares) used in media library.

**Default value:** 150

###upload_dir###

Dirname of your public directory. It's used to check thumb existence in thumb twig helper.

**Default value:** 'web'

###translation_domain###

Domain name for translations. For instance two languages are provided (en & fr). To override the domain name, change this parameter to whatever you want.

**Default value:** 'ComurImageBundle'

###gallery_thumb_size###

That's the image size in pixels that you want to show in gallery widget. Gallery widget will automaticaly create square thums having this size and show them in the gallery widget.

**Default value:** 150

###gallery_dir###

That's the gallery directory name. The widget will store all gallery images in a specific directory inside the root directory that you will give when you will add the widget to your forms.

**For eg.** if you put 'uploads/images' as webDir when you add the widget, gallery images will be stored in 'uploads/images/*[gallery_dir]*'. This is added to make gallery use easier so you don't have to add new functions to your entities to get gallery dirs.

#Usage#


There are two widgets provided with this bundle. They both have exacly same config parameters.

Image widget
------------

Use widget in your forms (works with SonataAdmin too) to create a simple image field :
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
	    // get your entity related with your form type
	    $myEntity = $builder->getForm()->getData();
	    ...
	    ->add('image', CroppableImageType::class, array(
	        'uploadConfig' => array(
	            'uploadRoute' => 'comur_api_upload', 		//optional
	            'uploadUrl' => $myEntity->getUploadRootDir(),       // required - see explanation below (you can also put just a dir path)
	            'webDir' => $myEntity->getUploadDir(),				// required - see explanation below (you can also put just a dir path)
	            'fileExt' => '*.jpg;*.gif;*.png;*.jpeg', 	//optional
	            'libraryDir' => null, 						//optional
	            'libraryRoute' => 'comur_api_image_library', //optional
	            'showLibrary' => true, 						//optional
	            'saveOriginal' => 'originalImage'			//optional
	        ),
	        'cropConfig' => array(
	            'minWidth' => 588,
	            'minHeight' => 300,
	            'aspectRatio' => true, 				//optional
	            'cropRoute' => 'comur_api_crop', 	//optional
	            'forceResize' => false, 			//optional
	            'thumbs' => array( 					//optional
	            	array(
	            		'maxWidth' => 180,
	            		'maxHeight' => 400,
	            		'useAsFieldImage' => true  //optional
	            	)
	            )
	        )
	    ))
    
You need to create a field (named image in this example but you can choose whatever you want):

	// YourBundle\Entity\YourEntity.php
	
	…
	
	/**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $image;
    
    …
    
And create your functions in your entity to have directory paths, for ex :

	public function getUploadRootDir()
	{
	    // absolute path to your directory where images must be saved
	    return __DIR__.'/../../../../../web/'.$this->getUploadDir();
	}
	
	public function getUploadDir()
	{
	    return 'uploads/myentity';
	}
	
	public function getAbsolutePath()
	{
	    return null === $this->image ? null : $this->getUploadRootDir().'/'.$this->image;
	}
	
	public function getWebPath()
	{
	    return null === $this->image ? null : '/'.$this->getUploadDir().'/'.$this->image;
	}
    
That's all ! This will add an image preview with an edit button in your form and will let you upload / select from library and crop images without reloading the page.

To save original image path, you have to create another field and name it same as saveOriginal parameter:

	// YourBundle\Entity\YourEntity.php
	
	…
	
	/**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $originalImage;
    
    …



I will put a demo soon…

Gallery widget
------------

Use widget in your forms (works with SonataAdmin too) to create a **sortable** list of images (so a gallery :)) stored in an array typed field :

	->add('gallery', CroppableGalleryType::class, array(
		//same parameters as comur_image
	))
	
And create your array typed field for storing images in it. Doctrine (or other ORM) will serialize this field to store it as string in the DB.

	// YourBundle\Entity\YourEntity.php
	
	…
	
	/**
     * @ORM\Column(type="array", nullable=true)
     */
    protected $gallery;
    
    …
    
And create your functions in your entity to have directory paths, for ex :

	public function getUploadRootDir()
    {
        // absolute path to your directory where images must be saved
        return __DIR__.'/../../../../../web/'.$this->getUploadDir();
    }

    public function getUploadDir()
    {
        return 'uploads/myentity';
    }

    public function getAbsolutePath()
    {
        return null === $this->image ? null : $this->getUploadRootDir().'/'.$this->image;
    }

    public function getWebPath()
    {
        return null === $this->image ? null : '/'.$this->getUploadDir().'/'.$this->image;
    }

That's all ! This will create a widget like on the following image when you will use it in your forms. **You can also reorder them since php serialized arrays are ordered**:

Gallery images will be stored in uploadUrl / gallery_dir (default is gallery). Cropped images will be stored in uploadUrl / gallery_dir / cropped_dir (same as image widget) and thumbs in uploadUrl / gallery_dir / cropped_dir / thumb_dir with specified width. So if you pu

##uploadConfig##

###uploadRoute (optional)###

Route called to send uploaded file. It's recommended to not change this parameter except if you know exactly what you do.

**Default value:** comur_api_upload

###uploadUrl (required)###

Absolute url to directory where put uploaded image. I recommend you to create a function in your entity and call it like it's showen in the example:
    
    public function getUploadRootDir()
    {
        // absolute path to your directory where images must be saved
        return __DIR__.'/../../../../../web/'.$this->getUploadDir();
    }

    public function getUploadDir()
    {
        return 'uploads/myentity';
    }

    public function getAbsolutePath()
    {
        return null === $this->image ? null : $this->getUploadRootDir().'/'.$this->image;
    }

    public function getWebPath()
    {
        return null === $this->image ? null : '/'.$this->getUploadDir().'/'.$this->image;
    }


###webDir (required)###

Url used to show your image in templates, must be relative url. If you created related functions as explained in uploadUrl section, then you can user getWebPath() function for webDir parameter.

###fileExt (optional)###

Permitted image extensions.

**Default value:** '*.jpg;*.gif;*.png;*.jpeg'

###libraryDir (optional)###

Directory to look into for images to show in image library.

**Default value:** uploadUrl

###libraryRoute (optional)###

Route called to get images to show in library. I recommend you to not change this parameter if you don't know exactly what it does.

**Default value:** comur_api_image_library

###showLibrary (optional)###

Set this to false if you don't want the user see existing images in libraryDir.

**Default value:** true

###saveOriginal (optional)###

Use this parameter if you want to save original file's path (for eg. to show big image in a lightbox). You have to put property name of your entity and the bundle will use it to save original file path in it.

**Attention:** This parameter is disabled for gallery for instance. It will be implemented soon.

**Default value:** false

##cropConfig##

###minWidth (optional)###

Minimum with of desired image.

**Default value:** 1

###minHeight (optional)###

Minimum height of desired image.

**Default value:** 1
###aspectRatio (optional)###

True to aspect ratio of crop screen.

**Default value:** true

###cropRoute (optional)###

Route to crop action. I recommend you to not change this parameter if you don't know exactly what it does.

**Default value:** comur_api_crop


###forceResize (optional)###

If true, system will resize image to fit minWidth and minHeight.

###thumbs (optional)###

Array of thums to create automaticly. System will resize images to fit maxWidth and maxHeight. It will put them in "uploadDir/cropped_images_dir/thumbs_dir/widthxheight-originalfilename.extension" so you can use included Thumb Twig extension to get them, ex:

```
{# your_themplate.html.twig #}
<img src="{{ entity.imagePath|thumb(45, 56) }}"
			
```

**New in 0.2.2:** You can use 'useAsFieldImage' option to use this thumb as image field's preview (in your form you will see this thumb instead of original cropped image). Usefull when you have big cropped images.

#TODO LIST#

* Create tests
* Add more comments in the code
* Think about removed image deletion (for now images are not deleted, you have to care about it by yourself…)
* Update existing images list dynamicly after an image upload

