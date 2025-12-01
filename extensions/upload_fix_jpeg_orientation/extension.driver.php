<?php

class extension_upload_fix_jpeg_orientation extends Extension
{

    /**
     * Delegates and callbacks
     *
     * @return array
     */
    public function getSubscribedDelegates()
    {
        return array(
            array(
                'page'     => '*',
                'delegate' => 'ManipulateTmpFile',
                'callback' => 'manipulateTmpFile'
            )
        );
    }

    public function manipulateTmpFile($context)
    {
        if (extension_loaded('gd') && function_exists('gd_info')) {
            $tmp_file = $context['tmp'];

            $mimetype = GENERAL::getMimeType($tmp_file);

            if ( $mimetype === 'image/jpeg' ) {
                try {
                    $exif = exif_read_data($tmp_file);
                    if ( isset($exif) && isset($exif['Orientation']) ) {
                        $this->rotateImage($tmp_file, $exif['Orientation']);
                    }
                }
                catch (Exception $ex) {
                    if (Symphony::Log()) {
                       Symphony::Log()->pushExceptionToLog($ex, true);
                    }
                }
            }
        }
    }

    private function rotateImage($tmp_file, $orientation)
    {
        $img = imagecreatefromjpeg($tmp_file);
        $ort = $orientation;

        if ($img !== false  && $ort > 1) {
            // image rotation
            if ( $ort === 7 || $ort === 8 ) {
                $img = imagerotate($img, 90, null);
            }
            if ( $ort === 3 || $ort === 4 ){
                $img = imagerotate($img, 180, null);
            }
            if ( $ort === 5 || $ort === 6 ) {
               $img = imagerotate($img, 270, null);
            }

            // flipping horizontally
            if ( $ort === 2 || $ort === 7 ) {
                imageflip($img, IMG_FLIP_HORIZONTAL);
            }

            // flipping vertically
            if ( $ort === 4 || $ort === 5 ) {
                imageflip($img, IMG_FLIP_VERTICAL);
            }

            if ( $img !== false ) {
                imagejpeg($img, $tmp_file, 100);

                if (class_exists('GdImage') && $img instanceof GdImage) {
                    unset($img);
                } elseif (is_resource($img)) {
                    imagedestroy($img);
                }
            }
        }
    }
}
