<?php

namespace Comur\ImageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

use Symfony\Component\Finder\Finder;

use Comur\ImageBundle\Handler\UploadHandler;

class UploadController extends Controller
{
    public function uploadImageAction(Request $request, $uploadUrl, $paramName, $webDir, $minWidth=1, $minHeight=1){
        
        $uploadUrl = urldecode($uploadUrl);
        $uploadUrl = substr($uploadUrl, -strlen('/')) === '/' ? $uploadUrl : $uploadUrl . '/';
        $response = new StreamedResponse();
        $webDir = urldecode($webDir);
        $webDir = substr($webDir, -strlen('/')) === '/' ? $webDir : $webDir . '/';
        $response->setCallback(function () use($uploadUrl, $paramName, $webDir, $minWidth, $minHeight) {
            new UploadHandler(array(
                'upload_dir' => $uploadUrl,
                'param_name' => $paramName,
                'file_name' => sha1(uniqid(mt_rand(), true)),
                'upload_url' => $webDir,
                'min_width' => $minWidth,
                'min_height' => $minHeight
                ));
        });
        return $response->send();
    }

    public function cropImageAction(Request $request, $uploadUrl, $webDir, $imageName, $x, $y, $w, $h, $tarW, $tarH)
    {
        $x = (int) round($x);
        $y = (int) round($y);
        $w = (int) round($w);
        $h = (int) round($h);
        $tarW = (int) round($tarW);
        $tarH = (int) round($tarH);

        $uploadUrl = urldecode($uploadUrl);
        $webDir = urldecode($webDir);

        $src = $uploadUrl.'/'.$imageName;

        $type = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));

        switch ($type) {
            case 'jpg':
            case 'jpeg':
                $srcFunc = 'imagecreatefromjpeg';
                $writeFunc = 'imagejpeg';
                $imageQuality = 100;
                break;
            case 'gif':
                $srcFunc = 'imagecreatefromgif';
                $writeFunc = 'imagegif';
                $imageQuality = null;
                break;
            case 'png':
                $srcFunc = 'imagecreatefrompng';
                $writeFunc = 'imagepng';
                $imageQuality = 9;
                break;
            default:
                return false;
        }

        $imgR = $srcFunc($src);
        
        if(round($w/$h, 2) != round($tarW/$tarH, 2)){
            $tarW = $w;
            $tarH = $h;
        }
        $dstR = imagecreatetruecolor( $tarW, $tarH );

        imagecopyresampled($dstR,$imgR,0,0,$x,$y,$tarW,$tarH,$w,$h);

        switch ($type) {
            case 'gif':
            case 'png':
                imagecolortransparent($dstR, imagecolorallocate($dstR, 0, 0, 0));
            case 'png':
                imagealphablending($dstR, false);
                imagesavealpha($dstR, true);
                break;
        }
        if (!is_dir($uploadUrl.'/'.$this->container->getParameter('comur_image.cropped_image_dir').'/')) {
            mkdir($uploadUrl.'/'.$this->container->getParameter('comur_image.cropped_image_dir').'/', 0755, true);
        }
        $ext = pathinfo($imageName, PATHINFO_EXTENSION);
        $imageName = sha1(uniqid(mt_rand(), true)).'.'.$ext;
        $src = $uploadUrl.'/'.$this->container->getParameter('comur_image.cropped_image_dir').'/'.$imageName;
        $writeFunc($dstR,$src,$imageQuality);

        return new Response(json_encode(array('success' => true, 'filename'=>$this->container->getParameter('comur_image.cropped_image_dir').'/'.$imageName)));
    }

    public function getLibraryImagesAction(Request $request){
        $finder = new Finder();

        $finder->sortByType();
        $finder->depth('== 0');
        $result = array();

        foreach ($finder->in($request->request->get('dir'))->files() as $file) {
            $path = split('web', $file->getRealPath());
            $result[] = $file->getFilename();
        }
        return new Response(json_encode($result));
    }
}
