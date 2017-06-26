<?php

namespace Intervention\Image\Commands;

/**
 * This command computes a perceptual hash of the content in an image.
 *
 * Based on work by Jens Segers and Kenneth Rapp.
 *
 * @see https://github.com/jenssegers/imagehash
 */
class HashCommand extends AbstractCommand
{
    /**
     * Computes a perceptual hash of a given image
     *
     * @param  \Intervention\Image\Image $image
     *
     * @return string
     */
    public function execute($image)
    {
        $size = 64;

        // resize the image, preserving the original image:

        $image = clone $image;

        $image->resize($size, $size);

        // Get luma value (YCbCr) from RGB colors and calculate the DCT for each row:

        $rows = [];

        for ($y = 0; $y < $size; $y++) {
            $row = [];

            for ($x = 0; $x < $size; $x++) {
                $rgb = $image->pickColor($x, $y, 'array');

                $row[$x] = floor(($rgb[0] * 0.299) + ($rgb[1] * 0.587) + ($rgb[2] * 0.114));
            }

            $rows[$y] = $this->dct($row);
        }

        // Calculate the DCT for each column:

        $matrix = [];
        $col = [];

        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y < $size; $y++) {
                $col[$y] = $rows[$y][$x];
            }

            $matrix[$x] = $this->dct($col);
        }

        // Carve out the lowest-frequency bins:

        $values = [];

        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $values[] = $matrix[$y][$x];
            }
        }


//        for ($bin = 0; $bin < 8; $bin++) {
//            for ($y = 0; $y < $bin; $y++) {
//                $values[] = $matrix[$bin][$y]; // top to bottom
//            }
//            for ($x = $bin; $x >= 0; $x--) {
//                $values[] = $matrix[$x][$bin]; // right to left
//            }
//        }
//
//        for ($i = 0; $i < 64; $i++) {
//            $ring = (int) floor(sqrt($i));
//            $ring_row_start = $ring * $ring;
//            $ring_col_start = $ring_row_start + $ring;
//
//            if ($i < $ring_col_start) {
//                $values[] = $matrix[$i - $ring_row_start][$ring];
//            } else {
//                $values[] = $matrix[$ring][$i - $ring_col_start];
//            }
//        }

        // Calculate the median:

        $median = $this->median($values);

        // Calculate the hash:

        $bin = "";

        foreach ($values as $value) {
            $bin .= $value > $median ? "1" : "0";
        }

        $hex = base_convert($bin, 2, 16);

        $this->setOutput($bin);

        return true;
    }

    /**
     * Perform a 1-dimensional Discrete Cosine Transformation.
     *
     * @param int[] $pixels
     *
     * @return int[]
     */
    protected function dct($pixels)
    {
        $transformed = [];

        $size = count($pixels);

        for ($i = 0; $i < $size; $i++) {
            $sum = 0;

            for ($j = 0; $j < $size; $j++) {
                $sum += $pixels[$j] * cos($i * pi() * ($j + 0.5) / ($size));
            }

            $sum *= sqrt(2 / $size);

            if ($i == 0) {
                $sum *= 1 / sqrt(2);
            }

            $transformed[$i] = $sum;
        }

        return $transformed;
    }

    /**
     * Get the median of the pixel values.
     *
     * @param int[] $values
     *
     * @return float
     */
    protected function median($values)
    {
        sort($values, SORT_NUMERIC);

        $middle = (int) floor(count($values) / 2);

        if (count($values) % 2) {
            $median = $values[$middle];
        } else {
            $low = $values[$middle];
            $high = $values[$middle + 1];
            $median = ($low + $high) / 2;
        }

        return $median;
    }
}
