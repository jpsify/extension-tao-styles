<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 *
 */

namespace oat\taoStyles\model\images;

use oat\oatbox\log\LoggerAwareTrait;

/**
 * Class ImageProcessor
 *
 * @package oat\taoStyles\model\images
 */
class ImageProcessor
{

    use LoggerAwareTrait;

    /**
     * Upload a logo to the tmp directory
     *
     * @return array
     */
    public function uploadLogo()
    {
        foreach(['name','type','size','tmp_name','error'] as $field) {
            if(!isset($_FILES['content'][$field])) {
                return ['error' => 'Failed to upload image'];
            }
        }

        if($_FILES['content']['error'] !== UPLOAD_ERR_OK){

            $this->logWarning('File upload failed with error ' . $_FILES['content']['error']);
            switch($_FILES['content']['error']){
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = __('Picture size must be lesser than: ') . ini_get('post_max_size');
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error = __('No file uploaded');
                    break;
                default:
                    $error = __('File upload failed');
                    break;
            }

            return ['error' => $error];
        }

        return $_FILES['content'];
    }


    /**
     * Resize an image
     *
     * @param $uploadResult
     *
     * @return array|string
     */
    public function buildResizedImage(array $uploadResult)
    {
        $errorText = 'File seems not to be a valid image: ' . $uploadResult['name'];

        // getImageSize will issue a warning on failure
        // that we need to mask
        $size = @getimagesize($uploadResult['tmp_name']);

        // no guarantee yet that this is a proper image file!
        if(empty($size)) {
            $this->logWarning($errorText);
            return ['error' => $errorText];
        }

        switch($size['mime']) {
            case 'image/jpeg':
                $inputMethod  = 'imagecreatefromjpeg';
                $outputMethod = 'imagejpeg';
                $extension    = 'jpg';
                break;

            case 'image/png':
                $inputMethod  = 'imagecreatefrompng';
                $outputMethod = 'imagepng';
                $extension    = 'png';
                break;

            case 'image/gif':
                $inputMethod  = 'imagecreatefromgif';
                $outputMethod = 'imagegif';
                $extension    = 'gif';
                break;

            default:
                $this->logWarning($errorText);
                return ['error' => $errorText];
        }

        $newHeight    = 52;
        $newWidth     = $newHeight * $size[0] / $size[1];
        $success      = false;

        ob_start();
        // $inputMethod returns false, if the image isn't a proper one
        // that means, at this point we can be sure
        $srcImage  = $inputMethod($uploadResult['tmp_name']);
        if($srcImage) {
            $newImage = imagecreatetruecolor($newWidth, $newHeight);
            if(function_exists('imageAlphaBlending') && function_exists('imageSaveAlpha')) {
                imageAlphaBlending($newImage, false);
                imageSaveAlpha($newImage, true);
            }
            imagecopyresampled($newImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $size[0], $size[1]);
            $success = $outputMethod($newImage);
            $output  = base64_encode(ob_get_contents());
            imagedestroy($newImage);
        }
        ob_end_clean();

        if(!$success) {
            $this->logWarning($errorText);
            return ['error' => $errorText];
        }

        return [
            'width'        => $newWidth,
            'height'       => $newHeight,
            'mime'         => $size['mime'],
            'image'        => $output,
            'extension'    => $extension
        ];
    }
}
