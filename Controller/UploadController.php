<?php

namespace Comur\ImageBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

use Symfony\Component\Finder\Finder;

use Comur\ImageBundle\Handler\UploadHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UploadController extends Controller
{
    /**
     * Save uploaded image according to comur_image field configuration
     *
     * @param Request $request
     *
     * @return Response
     */
    public function uploadImageAction(Request $request)
    {
        $config = json_decode($request->request->get('config'), true);

        $thumbsDir = $this->container->getParameter('comur_image.thumbs_dir');
        $thumbSize = $this->container->getParameter('comur_image.media_lib_thumb_size');
        $uploadUrl = $config['uploadConfig']['uploadUrl'];
        $uploadUrl = substr($uploadUrl, -strlen('/')) === '/' ? $uploadUrl : $uploadUrl . '/';

        $response = new StreamedResponse();

        if ($config['uploadConfig']['generateFilename']) {
            $filename = sha1(uniqid(mt_rand(), true));
        } else {
            $filename = $request->files->get('image_upload_file')->getClientOriginalName();
            if (file_exists($uploadUrl . $thumbsDir . '/' . $filename)) {
                $filename = time() . '-' . $filename;
            }
        }

        $handlerConfig = array(
            'upload_dir' => $uploadUrl,
            'param_name' => 'image_upload_file',
            'file_name' => $filename,
            'generated_file_name' => $config['uploadConfig']['generateFilename'],
            'upload_url' => $config['uploadConfig']['webDir'],
            'min_width' => $config['cropConfig']['minWidth'],
            'min_height' => $config['cropConfig']['minHeight'],
            'image_versions' => array(
                'thumbnail' => array(
                    'upload_dir' => $uploadUrl . $thumbsDir . '/',
                    'upload_url' => $config['uploadConfig']['webDir'] . '/' . $thumbsDir . '/',
                    'crop' => true,
                    'max_width' => $thumbSize,
                    'max_height' => $thumbSize
                )
            )
        );

        $transDomain = $this->container->getParameter('comur_image.translation_domain');

        $errorMessages = array(
            1 => $this->get('translator')->trans('The uploaded file exceeds the upload_max_filesize directive in php.ini', array(), $transDomain),
            2 => $this->get('translator')->trans('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form', array(), $transDomain),
            3 => $this->get('translator')->trans('The uploaded file was only partially uploaded', array(), $transDomain),
            4 => $this->get('translator')->trans('No file was uploaded', array(), $transDomain),
            6 => $this->get('translator')->trans('Missing a temporary folder', array(), $transDomain),
            7 => $this->get('translator')->trans('Failed to write file to disk', array(), $transDomain),
            8 => $this->get('translator')->trans('A PHP extension stopped the file upload', array(), $transDomain),
            'post_max_size' => $this->get('translator')->trans('The uploaded file exceeds the post_max_size directive in php.ini', array(), $transDomain),
            'max_file_size' => $this->get('translator')->trans('File is too big', array(), $transDomain),
            'min_file_size' => $this->get('translator')->trans('File is too small', array(), $transDomain),
            'accept_file_types' => $this->get('translator')->trans('Filetype not allowed', array(), $transDomain),
            'max_number_of_files' => $this->get('translator')->trans('Maximum number of files exceeded', array(), $transDomain),
            'max_width' => $this->get('translator')->trans('Image exceeds maximum width', array(), $transDomain),
            'min_width' => $this->get('translator')->trans('Image requires a minimum width (%min%)', array('%min%' => $config['cropConfig']['minWidth']), $transDomain),
            'max_height' => $this->get('translator')->trans('Image exceeds maximum height', array(), $transDomain),
            'min_height' => $this->get('translator')->trans('Image requires a minimum height (%min%)', array('%min%' => $config['cropConfig']['minHeight']), $transDomain),
            'abort' => $this->get('translator')->trans('File upload aborted', array(), $transDomain),
            'image_resize' => $this->get('translator')->trans('Failed to resize image', array(), $transDomain),
        );

        $response->setCallback(function () use ($handlerConfig, $errorMessages) {
            new UploadHandler($handlerConfig, true, $errorMessages);
        });

        return $response->send();
    }

    /**
     * Crop image using jCrop and upload config parameters and create thumbs if needed
     *
     * @param Request $request
     *
     * @return Response
     */
    public function cropImageAction(Request $request)
    {
        $config = json_decode($request->request->get('config'), true);
        $croppedImageDir = $this->container->getParameter('comur_image.cropped_image_dir');
        $thumbsDir = $this->container->getParameter('comur_image.thumbs_dir');
        $webDir = $config['uploadConfig']['webDir'];

        $params = $request->request->all();
        $x = (int)round($params['x']);
        $y = (int)round($params['y']);
        $w = (int)round($params['w']);
        $h = (int)round($params['h']);
        $tarW = (int)round($config['cropConfig']['minWidth']);
        $tarH = (int)round($config['cropConfig']['minHeight']);

        if ($x < 0) {
            $w = $w + $x;
            $x = 0;
        }

        if ($y < 0) {
            $h = $h + $y;
            $y = 0;
        }

        $forceResize = $config['cropConfig']['forceResize'];
        $uploadUrl = urldecode($config['uploadConfig']['uploadUrl']);
        $imageName = $params['imageName'];
        $src = $uploadUrl . '/' . $imageName;

        if (!is_dir($uploadUrl . '/' . $croppedImageDir . '/')) {
            mkdir($uploadUrl . '/' . $croppedImageDir . '/', 0755, true);
        }

        $ext = pathinfo($imageName, PATHINFO_EXTENSION);
        if ($config['uploadConfig']['generateFilename']) {
            $imageName = sha1(uniqid(mt_rand(), true)) . '.' . $ext;
        }

        $destSrc = $uploadUrl . '/' . $croppedImageDir . '/' . $imageName;
        $destW = $w;
        $destH = $h;

        if ($forceResize) {
            $destW = $tarW;
            $destH = $tarH;
            if (round($w / $h, 2) !== round($tarW / $tarH, 2)) {
                list($destW, $destH) = $this->getMinResizeValues($w, $h, $tarW, $tarH);
            }
        }

        $this->resizeCropImage($destSrc, $src, 0, 0, $x, $y, $destW, $destH, $w, $h);

        $galleryThumbOk = false;
        $isGallery = isset($config['uploadConfig']['isGallery']) ? $config['uploadConfig']['isGallery'] : false;
        $gThumbSize = $this->container->getParameter('comur_image.gallery_thumb_size');

        if ($isGallery) {
            if (!isset($config['cropConfig']['thumbs']) || !($thumbs = $config['cropConfig']['thumbs']) || !count($thumbs)) {
                $config['cropConfig']['thumbs'] = array();
            }
            $config['cropConfig']['thumbs'][] = array('maxWidth' => $gThumbSize, 'maxHeight' => $gThumbSize, 'forGallery' => true);
        }

        $previewSrc = '/' . $webDir . '/' . $croppedImageDir . '/' . $imageName;
        if (isset($config['cropConfig']['thumbs']) && ($thumbs = $config['cropConfig']['thumbs']) && count($thumbs)) {
            $thumbDir = $uploadUrl . '/' . $croppedImageDir . '/' . $thumbsDir . '/';
            if (!is_dir($thumbDir)) {
                mkdir($thumbDir);
            }

            foreach ($thumbs as $thumb) {
                $maxW = $thumb['maxWidth'];
                $maxH = $thumb['maxHeight'];

                if (!isset($thumb['forGallery']) && $maxW == $gThumbSize && $maxH == $gThumbSize) {
                    $galleryThumbOk = true;
                }

                if (isset($thumb['forGallery']) && $galleryThumbOk) {
                    continue;
                }

                list($w, $h) = $this->getMaxResizeValues($destW, $destH, $maxW, $maxH);

                $thumbName = $maxW . 'x' . $maxH . '-' . $imageName;
                $thumbSrc = $thumbDir . $thumbName;
                $this->resizeCropImage($thumbSrc, $destSrc, 0, 0, 0, 0, $w, $h, $destW, $destH);
                if (isset($thumb['useAsFieldImage']) && $thumb['useAsFieldImage']) {
                    $previewSrc = '/' . $webDir . '/' . $croppedImageDir . '/' . $thumbsDir . '/' . $thumbName;
                }
            }
        }

        $photoPath = $this->getParameter('kernel.root_dir') . '/../web/' . $previewSrc;
        $photoName = $this->get('photo.service')->saveFromLocalPath($photoPath);

        return new JsonResponse([
            'success' => true,
            'previewSrc' => $this->generateUrl('get_photo', ['key' => $photoName], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }

    /**
     * Calculates and returns maximum size to fit in maxW and maxH for resize
     */
    private function getMaxResizeValues($srcW, $srcH, $maxW, $maxH)
    {
        if ($srcH / $srcW < $maxH / $maxW) {
            $w = $maxW;
            $h = $srcH * ($maxW / $srcW);
        } else {
            $h = $maxH;
            $w = $srcW * ($maxH / $srcH);
        }
        return array($w, $h);
    }

    /**
     * Calculates and returns min size to fit in minW and minH for resize
     */
    private function getMinResizeValues($srcW, $srcH, $minW, $minH)
    {
        if ($srcH / $srcW > $minH / $minW) {
            $w = $minW;
            $h = $srcH * ($minW / $srcW);
        } else {
            $h = $minH;
            $w = $srcW * ($minH / $srcH);
        }
        return array($w, $h);
    }

    /**
     * Crops or resizes image and writes it on disk
     *
     * @return void
     */
    private function resizeCropImage($destSrc, $imgSrc, $destX, $destY, $srcX, $srcY, $destW, $destH, $srcW, $srcH)
    {
        $type = strtolower(pathinfo($imgSrc, PATHINFO_EXTENSION));

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
                return;
        }

        $imgR = $srcFunc($imgSrc);

        if (round($srcW / $srcH, 2) != round($destW / $destH, 2)) {
            $destW = $srcW;
            $destH = $srcH;
        }
        $dstR = imagecreatetruecolor($destW, $destH);

        if ($type == 'png') {
            imagealphablending($dstR, false);
            imagesavealpha($dstR, true);
        }

        imagecopyresampled($dstR, $imgR, $destX, $destY, $srcX, $srcY, $destW, $destH, $srcW, $srcH);

        switch ($type) {
            case 'gif':
            case 'png':
                imagecolortransparent($dstR, imagecolorallocate($dstR, 0, 0, 0));
                imagealphablending($dstR, false);
                imagesavealpha($dstR, true);
                break;
        }

        $writeFunc($dstR, $destSrc, $imageQuality);
    }
}
