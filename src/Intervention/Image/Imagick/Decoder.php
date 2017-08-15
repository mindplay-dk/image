<?php

namespace Intervention\Image\Imagick;

use Intervention\Image\Image;

class Decoder extends \Intervention\Image\AbstractDecoder
{
    /**
     * Initiates new image from path in filesystem
     *
     * @param  string $path
     * @return \Intervention\Image\Image
     */
    public function initFromPath($path)
    {
        $core = new \Imagick;

        try {

            $core->setBackgroundColor(new \ImagickPixel('transparent'));
            $core->readImage($path);
            $core->setImageType(defined('\Imagick::IMGTYPE_TRUECOLORALPHA') ? \Imagick::IMGTYPE_TRUECOLORALPHA : \Imagick::IMGTYPE_TRUECOLORMATTE);

        } catch (\ImagickException $e) {
            throw new \Intervention\Image\Exception\NotReadableException(
                "Unable to read image from path ({$path}).",
                0,
                $e
            );
        }

        // build image
        $image = $this->initFromImagick($core);
        $image->setFileInfoFromPath($path);

        return $image;
    }

    /**
     * Initiates new image from GD resource
     *
     * @param  Resource $resource
     * @return \Intervention\Image\Image
     */
    public function initFromGdResource($resource)
    {
        throw new \Intervention\Image\Exception\NotSupportedException(
            'Imagick driver is unable to init from GD resource.'
        );
    }

    /**
     * Initiates new image from Imagick object
     *
     * @param  Imagick $object
     * @return \Intervention\Image\Image
     */
    public function initFromImagick(\Imagick $object)
    {
        // currently animations are not supported
        // so all images are turned into static
        $object = $this->removeAnimation($object);

        // reset image orientation
        $object->setImageOrientation(\Imagick::ORIENTATION_UNDEFINED);

        // remove color profile, if present:
        $this->removeColorProfile($object);

        return new Image(new Driver, $object);
    }

    /**
     * Initiates new image from binary data
     *
     * @param  string $data
     * @return \Intervention\Image\Image
     */
    public function initFromBinary($binary)
    {
        $core = new \Imagick;

        try {

            $core->readImageBlob($binary);

        } catch (\ImagickException $e) {
            throw new \Intervention\Image\Exception\NotReadableException(
                "Unable to read image from binary data.",
                0,
                $e
            );
        }

        // build image
        $image = $this->initFromImagick($core);
        $image->mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $binary);

        return $image;
    }

    /**
     * Turns object into one frame Imagick object
     * by removing all frames except first
     *
     * @param  Imagick $object
     * @return Imagick
     */
    private function removeAnimation(\Imagick $object)
    {
        $imagick = new \Imagick;

        foreach ($object as $frame) {
            $imagick->addImage($frame->getImage());
            break;
        }

        $object->destroy();

        return $imagick;
    }

    const ICC_SRGB = "sRGB_IEC61966-2-1_black_scaled.icc";
    const ICC_CMYK = 'USWebCoatedSWOP.icc';

    /**
     * Remove any ICC color profile and convert to standard RGB color space
     *
     * @param  \Imagick $object
     */
    private function removeColorProfile(\Imagick $object)
    {
        try {
            $colorspace = $object->getImageColorspace();

            $profiles = $object->getImageProfiles("*", false);

            $has_icc_profile = array_search('icc', $profiles) !== false;

            if ($colorspace === \Imagick::COLORSPACE_CMYK) {
                if (! $has_icc_profile) {
                    $this->applyProfile($object, self::ICC_CMYK);
                }

                $this->convertToRGB($object);
            } elseif ($colorspace !== \Imagick::COLORSPACE_GRAY) {
                if ($has_icc_profile) {
                    $this->convertToRGB($object);
                }
            }
        } catch (\ImagickException $e) {
            // as a last resort, remove unsupported or defective ICC profile:
            try {
                $object->removeImageProfile('icc');
            } catch (\ImagickException $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);
            }
        }
    }

    private function convertToRGB(\Imagick $object)
    {
        $this->applyProfile($object, self::ICC_SRGB);

        $object->setImageColorSpace(\Imagick::COLORSPACE_SRGB);
    }

    private function applyProfile(\Imagick $object, $profile_name)
    {
        $profile = file_get_contents(dirname(__DIR__, 4) . "/icc/" . $profile_name);

        $object->profileImage('icc', $profile);
    }
}
