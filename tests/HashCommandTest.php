<?php

use Intervention\Image\AbstractDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Gd;
use Intervention\Image\Imagick;

// here's a JS version as well:
// https://jsfiddle.net/mindplay/n8wq4e5n

class HashCommandTest extends PHPUnit_Framework_TestCase
{
    public function testHashWithGD()
    {
//        $manager = new ImageManager(["driver" => new Gd\Driver()]);
//
//        $files = [];
//
//        $num_images = 3;
//
//        foreach (range(1, $num_images) as $i) {
//            $files[] = sprintf("high/%04d.jpg", $i);
//            $files[] = sprintf("low/%04d.jpg", $i);
//            $files[] = sprintf("small/%04d.jpg", $i);
//        }
//
//        $map = [];
//
//        while (ob_get_level()) {
//            ob_end_flush();
//        }
//
//        foreach ($files as $file) {
//            $map[$file] = $manager->make(__DIR__ . "/photos/{$file}")->hash();
//            echo ".";
//            flush();
//        }
//
//        echo "\n";
//
//        $stats = [];
//
//        foreach ($map as $file => $hash) {
//            $stats[$hash][] = $file;
//        }
//
//        var_dump($stats);
//
//        die();

        $this->hashWithDriver(new Gd\Driver());
        $this->hashWithDriver(new Imagick\Driver());
    }

    private function hashWithDriver(AbstractDriver $driver)
    {
        $manager = new ImageManager(["driver" => $driver]);

        $low = $manager->make(__DIR__ . '/images/forest-low.jpg')->hash();
        $high = $manager->make(__DIR__ . '/images/forest-high.jpg')->hash();
        $small = $manager->make(__DIR__ . '/images/forest-small.jpg')->hash();
        $watermarked = $manager->make(__DIR__ . '/images/forest-watermarked.jpg')->hash();

        echo base_convert($low, 16, 2) . "\n";
        echo base_convert($high, 16, 2) . "\n";
        echo base_convert($small, 16, 2) . "\n\n";
        echo base_convert($watermarked, 16, 2) . "\n";

        var_dump($this->distance($high, $low));
        var_dump($this->distance($high, $small));
        var_dump($this->distance($small, $low));
        var_dump($this->distance($high, $watermarked));

        var_dump($this->distance($high, $low, 6*6));
        var_dump($this->distance($high, $small, 6*6));
        var_dump($this->distance($small, $low, 6*6));
        var_dump($this->distance($high, $watermarked, 6*6));

//        die();

        $this->assertLessThan(3, $this->distance($low, $high));
        $this->assertLessThan(3, $this->distance($high, $small));
        $this->assertLessThan(3, $this->distance($small, $low));

        $this->assertEquals(0, $this->distance($low, $high, 6*6));
        $this->assertEquals(0, $this->distance($high, $small, 6*6));
        $this->assertEquals(0, $this->distance($small, $low, 6*6));

        $this->assertLessThan(5, $this->distance($low, $watermarked));
        $this->assertLessThan(5, $this->distance($high, $watermarked));
        $this->assertLessThan(5, $this->distance($small, $watermarked));
    }

    private function distance($a, $b, $bits = 64)
    {
        $hash1 = base_convert($a, 16, 2);
        $hash2 = base_convert($b, 16, 2);

        $dist = 0;

        for ($i = 0; $i < $bits; $i++) {
            if ($hash1{$i} !== $hash2{$i}) {
                $dist++;
            }
        }

        return $dist;
    }

    /**
     * @param $hash
     *
     * @return string
     */
    private function toBinary($hash)
    {
        return sprintf("%064s", base_convert($hash, 16, 2));
    }

    private function diffBits($a, $b)
    {
        $result = "";

        for ($i=0; $i<strlen($a); $i++) {
            $result .= $a{$i} === $b{$i} ? "0" : "1";
        }

        return $result;
    }
}
