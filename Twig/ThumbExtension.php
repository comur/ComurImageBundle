<?php
namespace Comur\ImageBundle\Twig;

class ThumbExtension extends \Twig_Extension
{
    protected $croppedDir;
    protected $thumbsDir;
    protected $webDir;

    public function __construct($croppedDir, $thumbsDir, $container, $webDirName)
    {
        $this->croppedDir = $croppedDir;
        $this->thumbsDir = $thumbsDir;
        $this->webDir = $container->get('kernel')->getRootdir().'/../' . $webDirName;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('thumb', array($this, 'getThumb')),
        );
    }

    /**
     * Returns thumb file if exists
     * @param string $origFilePath web path to original file (relative, ex: uploads/members/cropped/azda4qs.jpg)
     * @param integer $width desired thumb's width
     * @param integer $height desired thumb's height
     * @return string thumbnail path if thumbnail exists, if not returns original file path
     */
    public function getThumb($origFilePath, $width, $height)
    {
        $pathInfo = pathinfo($origFilePath);
        $uploadDir = $pathInfo['dirname'] . '/';
        $filename = $pathInfo['basename'];

        $thumbSrc = $uploadDir . $this->thumbsDir . '/' . $width . 'x' . $height . '-' .$filename;

        // return $this->webDir.'/'.$thumbSrc;

        return file_exists($this->webDir.'/'.$thumbSrc) ? $thumbSrc : $uploadDir . $filename;
    }

    public function getName()
    {
        return 'comur_thumb_extension';
    }
}