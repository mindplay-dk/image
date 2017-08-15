<?php

class ColorProfileTest extends PHPUnit_Framework_TestCase
{
    public function testColorManagement()
    {
        $this->convertWith("icc-1998.jpg", "gd");
        $this->convertWith("icc-1998.jpg", "imagick");

        $this->convertWith("icc-cmyk.jpg", "gd");
        $this->convertWith("icc-cmyk.jpg", "imagick");
    }

    public function convertWith($filename, $driver)
    {
        $manager = new \Intervention\Image\ImageManager(array(
            'driver' => $driver
        ));

        $image = $manager->make("tests/images/{$filename}");

        $basename = basename($filename);

        $image->save("tests/tmp/{$basename}-{$driver}.jpg");
    }
}
