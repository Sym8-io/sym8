<?php

require_once(realpath(dirname(__FILE__).'/../') . '/class.imagefilter.php');

Class FilterCrop extends ImageFilter
{

    const TOP_LEFT = 1;
    const TOP_MIDDLE = 2;
    const TOP_RIGHT = 3;
    const MIDDLE_LEFT = 4;
    const CENTER = 5;
    const MIDDLE_RIGHT = 6;
    const BOTTOM_LEFT = 7;
    const BOTTOM_MIDDLE = 8;
    const BOTTOM_RIGHT = 9;

    public static function run($res, $width, $height, $anchor=self::TOP_LEFT, $background_fill = null)
    {

        $dst_w = Image::width($res);
        $dst_h = Image::height($res);

        if (!empty($width) && !empty($height)) {
            $dst_w = $width;
            $dst_h = $height;
        } elseif (empty($height)) {
            $ratio = ($dst_h / $dst_w);
            $dst_w = $width;
            $dst_h = round($dst_w * $ratio);
        } elseif (empty($width)) {
            $ratio = ($dst_w / $dst_h);
            $dst_h = $height;
            $dst_w = round($dst_h * $ratio);
        }

        $tmp = imagecreatetruecolor($dst_w, $dst_h);

        self::__fill($res, $tmp, $background_fill);

        $image_width = Image::width($res);
        $image_height = Image::height($res);

        list($src_x, $src_y, $dst_x, $dst_y) = self::__calculateDestSrcXY($dst_w, $dst_h, $image_width, $image_height, $image_width, $image_height, $anchor);

        imagecopyresampled($tmp, $res, $src_x, $src_y, $dst_x, $dst_y, $image_width, $image_height, $image_width, $image_height);

        if (class_exists('GdImage') && $res instanceof GdImage) {
            unset($res);
        } elseif (is_resource($res)) {
            imagedestroy($res);
        }

        return $tmp;
    }

    protected static function __calculateDestSrcXY($width, $height, $src_w, $src_h, $dst_w, $dst_h, $position=self::TOP_LEFT)
    {
        $ix = 0;
        $iy = 0;

        if ($width < $src_w) {
            $mx = array(
                0,
                ceil(($src_w * 0.5) - ($width * 0.5)),
                $src_w - $width
            );
        } else {
            $mx = array(
                0,
                ceil(($width * 0.5) - ($src_w * 0.5)),
                $width - $src_w
            );
        }

        if ($height < $src_h) {
            $my = array(
                0,
                ceil(($src_h * 0.5) - ($height * 0.5)),
                $src_h - $height
            );
        } else {
            $my = array(
                0,
                ceil(($height * 0.5) - ($src_h * 0.5)),
                $height - $src_h
            );
        }

        switch ($position) {
            case 1:
                break; // top-left
            case 2:
                $ix = 1;
                break; // top-center
            case 3:
                $ix = 2;
                break; // top-right
            case 4:
                $iy = 1;
                break; // middle-left
            case 5:
                $ix = 1;
                $iy = 1;
                break; // center
            case 6:
                $ix = 2;
                $iy = 1;
                break; // middle-right
            case 7:
                $iy = 2;
                break; // bottom-left
            case 8:
                $ix = 1;
                $iy = 2;
                break; // bottom-center
            case 9:
                $ix = 2;
                $iy = 2;
                break; // bottom-right
        }

        $a = ($width  >= $dst_w ? $mx[$ix] : 0);
        $b = ($height >= $dst_h ? $my[$iy] : 0);
        $c = ($width  <  $dst_w ? $mx[$ix] : 0);
        $d = ($height <  $dst_h ? $my[$iy] : 0);

        return array($a, $b, $c, $d);
    }

}
