<?php
/**
 * Image 處理器
 * <p>最後修改時間: 2017-08-17</p>
 * <p>最後修改人員: Lion</p>
 * @author lion
 */

namespace lib;

class image
{
    const
        Center = 'Center';

    private
        $attr,
        $composite_offset_x,
        $composite_offset_y,
        $exif,
        $path,
        $pathOfOut,
        $height,
        $heightOfOut,
        $imagick,
        $sizeCodeOfOut,
        $quality,
        $qualityOfOut,
        $sizeCode,
        $type,//1 = GIF，2 = JPG，3 = PNG，4 = SWF，5 = PSD，6 = BMP，7 = TIFF(intel byte order)，8 = TIFF(motorola byte order)，9 = JPC，10 = JP2，11 = JPX，12 = JB2，13 = SWC，14 = IFF，15 = WBMP，16 = XBM
        $type_correspond = [1 => 'gif', 2 => 'jpg', 3 => 'png', 6 => 'bmp'],
        $typeOfOut,
        $width,
        $widthOfOut;

    public static
        $usableOfImagick = false;

    function __construct()
    {
        if (self::$usableOfImagick) {
            $this->imagick = new \Imagick();

            $this->imagick->setResolution(108, 108);//max 300, 300; must be called before loading or creating an image.
        }
    }

    function __destruct()
    {
        if (self::$usableOfImagick) {
            $this->imagick->clear();
        }
    }

    function composite(self $Image, $offset)
    {
        if (is_array($offset)) {
            $x = 0;

            if (isset($offset[0])) {
                if (is_numeric($offset[0])) {
                    $x = $offset[0];
                } else {
                    switch ($offset[0]) {
                        case self::Center:
                            $x = ($this->getWidthOfOut() - $Image->getWidthOfOut()) / 2;
                            break;
                    }
                }
            }

            $y = 0;

            if (isset($offset[1])) {
                if (is_numeric($offset[1])) {
                    $y = $offset[1];
                } else {
                    switch ($offset[0]) {
                        case self::Center:
                            $y = ($this->getHeightOfOut() - $Image->getHeightOfOut()) / 2;
                            break;
                    }
                }
            }

            $this->setCompositeOffsetX($x);
            $this->setCompositeOffsetY($y);
        } else {
            $this->setCompositeOffset($Image, $offset);
        }

        if (self::$usableOfImagick) {
            $this->imagick->compositeImage($Image->imagick, \Imagick::COMPOSITE_DEFAULT, $this->getCompositeOffsetX(), $this->getCompositeOffsetY());
        }

        return $this;
    }

    function convertSizeCode($size)
    {
        $class = new \ReflectionClass ('\\config\\image');

        $constants = $class->getConstants();

        $constName = 'Unknown';

        foreach ($constants as $name => $value) {
            if ($value == $size) {
                $constName = $name;
                break;
            }
        }

        return $constName;
    }

    /**
     * 取得屬性
     * @return string
     */
    function getAttr()
    {
        return $this->attr;
    }

    protected function getCompositeOffsetX()
    {
        return (int)$this->composite_offset_x ?? 0;
    }

    protected function getCompositeOffsetY()
    {
        return (int)$this->composite_offset_y ?? 0;
    }

    /**
     * 取得主要顏色 Hex
     * @return mixed
     * @throws \Exception
     */
    function getMainHex()
    {
        $widthRate = (float)$this->getWidth() / 16;
        $heightRate = (float)$this->getHeight() / 16;

        $rate = ($widthRate > $heightRate) ? $widthRate : $heightRate;

        $width = round($this->getWidth() / $rate);
        $height = round($this->getHeight() / $rate);

        $im_new = imagecreatetruecolor($width, $height);

        switch ($this->type) {
            case 1:
                $im_source = imagecreatefromgif($this->path);
                break;

            case 2:
                $im_source = imagecreatefromjpeg($this->path);
                break;

            case 3:
                $im_source = imagecreatefrompng($this->path);

                imagecolortransparent($im_new, imagecolorallocatealpha($im_new, 0, 0, 0, 127));
                imagealphablending($im_new, false);
                imagesavealpha($im_new, true);
                break;

            case 6:
                $im_source = imagecreatefromwbmp($this->path);
                break;

            default:
                //^ 用 imagecreatefromstring ?
                throw new \Exception('Unknown case');
                break;
        }

        imagecopyresampled($im_new, $im_source, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());

        $area = $width * $height;
        $histogram = [];

        for ($i = 0; $i < $width; ++$i) {
            for ($j = 0; $j < $height; ++$j) {
                // get the rgb value for current pixel
                $rgb = ImageColorAt($im_new, $i, $j);

                // extract each value for r, g, b
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // get the Value from the RGB value
                $v = round(($r + $g + $b) / 3);

                // add the point to the histogram
                if (!isset($histogram[$v])) $histogram[$v] = 0;

                $histogram[$v] += $v / $area;
                $histogram_color[$v] = rgb2hex($r, $g, $b);
            }
        }

        unset($histogram[255]);//2017-05-12 Lion: 255 目前都會取呈白色，因此排除

        return $histogram_color[array_search(max($histogram), $histogram)];

        /*
        $Imagick = new \Imagick($this->image);

        // Scale down to 1x1 pixel to make Imagick do the average
        $Imagick->scaleimage(1, 1);

        if ($pixels = $Imagick->getimagehistogram()) {
            $rgb = reset($pixels)->getcolor();

            $hex = rgb2hex($rgb['r'], $rgb['g'], $rgb['b']);
        } else {
            $hex = '#ffffff';
        }

        return $hex;
        */
    }

    /**
     * 取得高度
     * @return integer
     */
    function getHeight()
    {
        return $this->height;
    }

    /**
     * 取得輸出高度
     * @return integer
     */
    function getHeightOfOut()
    {
        return $this->heightOfOut ?? null;
    }

    /**
     * 取得輸出路徑
     * @return mixed
     */
    function getOutPath()
    {
        return $this->pathOfOut;
    }

    /**
     * 取得寬度
     * @return integer
     */
    function getWidth()
    {
        return $this->width;
    }

    /**
     * 取得輸出寬度
     * @return integer
     */
    function getWidthOfOut()
    {
        return $this->widthOfOut ?? null;
    }

    /**
     * 取得檔案路徑
     * @return mixed
     */
    function getPath()
    {
        return $this->path;
    }

    /**
     * 取得品質
     * @return int
     */
    function getQuality()
    {
        if (!isset($this->quality)) $this->quality = shell_exec('identify -format %Q ' . escapeshellarg($this->path));

        return $this->quality;
    }

    /**
     * 取得輸出的尺寸代號
     * @return mixed
     */
    function getSizeCodeOfOut()
    {
        return $this->sizeCodeOfOut;
    }

    /**
     * 取得原絕對路徑
     * @param string $file
     * @return string
     */
    function getSource($file)
    {
        $pathinfo = pathinfo($file);

        if (isset(explode('@', $pathinfo['filename'])[1])) $file = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . preg_replace('/(@[a-zA-Z]+[0-9]+)$/', '', $pathinfo['filename']) . '.' . $pathinfo['extension'];

        return $file;
    }

    /**
     * 取得類型
     * @return string
     */
    function getType()
    {
        return $this->type;
    }

    /**
     * 取得輸出的類型
     * @return string
     */
    function getTypeOfOut()
    {
        return $this->typeOfOut;
    }

    /**
     * 取得輸出的品質
     * @return mixed
     */
    function getQualityOfOut()
    {
        if (!isset($this->qualityOfOut)) $this->qualityOfOut = $this->getQuality();

        return $this->qualityOfOut;
    }

    /**
     * 儲存
     * @param null $pathOfOut
     * @param bool $overwrite
     * @param bool $delete
     * @param bool $suffix
     * @return bool
     */
    function save($pathOfOut = null, $overwrite = false, $delete = false, $suffix = true)
    {
        if ($this->path === null) {
            $boolean = false;

            goto _return;
        }

        if ($pathOfOut === null) {
            $pathinfo = pathinfo($this->path);

            $filename = ($suffix && ($this->getWidthOfOut() != $this->getWidth() || $this->getHeightOfOut() != $this->getHeight())) ? $pathinfo['filename'] . '@' . $this->getSizeCodeOfOut() : $pathinfo['filename'];

            $this->pathOfOut = $pathinfo['dirname'] . DIRECTORY_SEPARATOR . $filename . '.' . $this->type_correspond[$this->getTypeOfOut()];
        } else {
            $this->pathOfOut = $pathOfOut;
        }

        if (!is_file($this->pathOfOut) || $overwrite) {
            if (self::$usableOfImagick) {
                $this->setCorrect();

                if ($this->getTypeOfOut() == 2) {
                    $this->imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);

                    if ($this->getQualityOfOut() !== null && $this->getQualityOfOut() != $this->getQuality()) $this->imagick->setImageCompressionQuality($this->getQualityOfOut());

                    $this->imagick->setInterlaceScheme(\Imagick::INTERLACE_PLANE);//參考 http://stackoverflow.com/questions/7261855/recommendation-for-compressing-jpg-files-with-imagemagick
                }

                $this->imagick->writeImage($this->pathOfOut);
            } else {
                switch ($this->type) {
                    case 1:
                        $im_source = imagecreatefromgif($this->path);
                        break;

                    case 2:
                        $im_source = imagecreatefromjpeg($this->path);

                        if (isset($this->exif['Orientation'])) {
                            //翻轉來源圖像
                            switch ($this->exif['Orientation']) {
                                case 0: // undefined?
                                case 1: // nothing
                                    break;

                                case 2: // horizontal flip
                                    imageflip($im_source, 1);
                                    break;

                                case 3: // 180 rotate left
                                    $im_source = imagerotate($im_source, 180, 0);
                                    break;

                                case 4: // vertical flip
                                    imageflip($im_source, 2);
                                    break;

                                case 5: // vertical flip + 90 rotate right
                                    imageflip($im_source, 2);
                                    $im_source = imagerotate($im_source, -90, 0);
                                    break;

                                case 6: // 90 rotate right
                                    $im_source = imagerotate($im_source, -90, 0);
                                    break;

                                case 7: // horizontal flip + 90 rotate right
                                    imageflip($im_source, 1);
                                    $im_source = imagerotate($im_source, -90, 0);
                                    break;

                                case 8: // 90 rotate left
                                    $im_source = imagerotate($im_source, 90, 0);
                                    break;

                                default:
                                    \model\log::setException(\lib\exception::LEVEL_NOTICE, 'Unknown case. "' . $this->exif['Orientation'] . '" of orientation by "' . $this->pathOfOut . '".');
                                    break;
                            }
                        }
                        break;

                    case 3:
                        $im_source = imagecreatefrompng($this->path);
                        break;

                    case 6:
                        $im_source = imagecreatefromwbmp($this->path);
                        break;

                    default:
                        \model\log::setException(\lib\exception::LEVEL_NOTICE, 'Unknown case. "' . $this->type . '" of type by "' . $this->pathOfOut . '".');
                        break;
                }

                /**
                 * 創建圖像標識符
                 * 2014-08-01: 應該不是判斷圖檔類型來使用 imagecreate 或 imagecreatetruecolor, 而是要判斷圖色構成(方法尋找中..)
                 */
                $im_new = imagecreatetruecolor($this->getWidthOfOut(), $this->getHeightOfOut());

                switch ($this->typeOfOut) {
                    case 1:
                    case 3:
                        imagecolortransparent($im_new, imagecolorallocatealpha($im_new, 0, 0, 0, 127));
                        imagealphablending($im_new, false);
                        imagesavealpha($im_new, true);
                        break;

                    case 2:
                        break;

                    default:
                        \model\log::setException(\lib\exception::LEVEL_NOTICE, 'Unknown case. "' . $this->typeOfOut . '" of output type.');
                        break;
                }

                imagecopyresampled($im_new, $im_source, 0, 0, 0, 0, $this->getWidthOfOut(), $this->getHeightOfOut(), $this->getWidth(), $this->getHeight());

                //輸出目標圖像
                switch ($this->typeOfOut) {
                    case 1:
                        imagegif($im_new, $this->pathOfOut);
                        break;

                    case 2:
                        call_user_func_array('imagejpeg', ($this->getQualityOfOut() !== null && $this->getQualityOfOut() != $this->getQuality()) ? [$im_new, $this->pathOfOut, $this->getQualityOfOut()] : [$im_new, $this->pathOfOut]);
                        imageinterlace($im_new, 1);
                        break;

                    case 3:
                        imagepng($im_new, $this->pathOfOut);
                        break;

                    case 6:
                        imagewbmp($im_new, $this->pathOfOut);
                        break;

                    default:
                        \model\log::setException(\lib\exception::LEVEL_NOTICE, 'Unknown case. "' . $this->typeOfOut . '" of output type.');
                        break;
                }

                //釋放圖像內存
                imagedestroy($im_source);
                imagedestroy($im_new);
            }
        }

        if ($delete && $this->getSource($this->pathOfOut) != $this->getSource($this->path)) {
            \lib\file::delete($this->path);
        }

        $boolean = true;

        _return:

        return $boolean;
    }

    /**
     * 設置
     * @param $path : 檔案絕對路徑
     * @param bool $source : 是否尋回原檔案進行處理
     * @return $this
     */
    function set($path, $source = true)
    {
        if (!is_file($path)) {
            \model\log::setException(\lib\exception::LEVEL_NOTICE, '"' . $path . '" is not a file.');

            goto _return;
        }

        if ($source) $path = $this->getSource($path);

        if (self::$usableOfImagick) {
            try {
                $this->imagick->readimage($path);
            } catch (\ImagickException $exception) {
                \model\log::setException(\lib\exception::LEVEL_NOTICE, '"' . $path . '" is not an image, ' . $exception->getMessage());

                goto _return;
            }
        }

        $this->pathOfOut = $this->path = $path;

        list ($this->width, $this->height, $this->type, $this->attr) = getimagesize($this->path);

        /**
         * 2014-07-29:
         *     有些圖像會有無法解讀的 exif, 因此用 @ 屏蔽
         * 2014-05-01:
         *     處理數位圖像翻轉的情況，其中正負 90 度(以及其倍數)翻轉的圖像，用 getimagesize 取得的 $width 和 $height 會和實際相反，因此對換；
         *     另外 exif_read_data 僅能支持 JPEG、TIFF
         */
        if (in_array($this->type, [2, 8])) {
            $this->exif = @exif_read_data($this->path);
        }

        if (isset($this->exif['Orientation'])) {
            //width, height 互換
            switch ($this->exif['Orientation']) {
                case 5:
                case 6:
                case 7:
                case 8:
                    $tmp0 = $this->height;
                    $this->height = $this->width;
                    $this->width = $tmp0;
                    break;
            }
        }

        $this->setWidthOfOut($this->width);
        $this->setHeightOfOut($this->height);
        $this->setTypeOfOut($this->type);

        _return:

        return $this;
    }

    protected function setCompositeOffset(self $Image, $offset)
    {
        switch ($offset) {
            case self::Center:
                $this->setCompositeOffsetX(($this->getWidthOfOut() - $Image->getWidthOfOut()) / 2);
                $this->setCompositeOffsetY(($this->getHeightOfOut() - $Image->getHeightOfOut()) / 2);
                break;
        }
    }

    protected function setCompositeOffsetX($x)
    {
        $this->composite_offset_x = (int)$x;
    }

    protected function setCompositeOffsetY($y)
    {
        $this->composite_offset_y = (int)$y;
    }

    /**
     * 將檔案方向轉正
     */
    protected function setCorrect()
    {
        if (self::$usableOfImagick) {
            if ($this->getType() == 2 && isset($this->exif['Orientation'])) {
                switch ($this->exif['Orientation']) {
                    case 0: // undefined?
                    case 1: // nothing
                        break;

                    case 2: // horizontal flip
                        $this->imagick->flopImage();
                        break;

                    case 3: // 180 rotate left
                        $this->imagick->rotateImage(new \ImagickPixel(), 180);
                        break;

                    case 4: // vertical flip
                        $this->imagick->flipImage();
                        break;

                    case 5: // vertical flip + 90 rotate right
                        $this->imagick->flipImage();
                        $this->imagick->rotateImage(new \ImagickPixel(), 90);
                        break;

                    case 6: // 90 rotate right
                        $this->imagick->rotateImage(new \ImagickPixel(), 90);
                        break;

                    case 7: // horizontal flip + 90 rotate right
                        $this->imagick->flopImage();
                        $this->imagick->rotateImage(new \ImagickPixel(), 90);
                        break;

                    case 8: // 90 rotate left
                        $this->imagick->rotateImage(new \ImagickPixel(), -90);
                        break;

                    default:
                        \model\log::setException(\lib\exception::LEVEL_NOTICE, 'Unknown case. "' . $this->exif['Orientation'] . '" of orientation by "' . $this->pathOfOut . '".');
                        break;
                }

                $this->imagick->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);

                $this->exif['Orientation'] = \Imagick::ORIENTATION_TOPLEFT;//2017-08-17 Lion: 這行很重要，因為轉正了所以要更新，才不會再次翻轉
            }
        }
    }

    /**
     * 設置品質(目前僅支持 jpg)
     * @param $quality
     * @return $this
     */
    function setQuality($quality)
    {
        $this->qualityOfOut = $quality;

        return $this;
    }

    /**
     * 設置比例寬、高
     * @param $size
     * @return $this
     */
    function setScaleSize($size)
    {
        if ($this->path === null) goto _return;

        $this->sizeCodeOfOut = $this->convertSizeCode($size);

        if ($this->getWidth() != $size || $this->getHeight() != $size) {
            $widthRate = (float)$this->getWidth() / $size;
            $heightRate = (float)$this->getHeight() / $size;

            $rate = ($widthRate > $heightRate) ? $widthRate : $heightRate;

            $this->setWidthOfOut(round($this->getWidth() / $rate));
            $this->setHeightOfOut(round($this->getHeight() / $rate));

            if (self::$usableOfImagick) {
                $this->setCorrect();//2017-08-17 Lion: 轉正需要在 resize 之前

                $this->imagick->resizeImage($this->getWidthOfOut(), $this->getHeightOfOut(), \Imagick::FILTER_CATROM, 1);
            }
        }

        _return:

        return $this;
    }

    /**
     * 設置寬、高
     * @param int $width : 寬
     * @param int $height : 高
     * @param bool $forced : 是否強制縮放為指定寬高
     * @return $this
     */
    function setSize($width = 100, $height = 100, $forced = false)
    {
        if ($this->getWidth() != $width || $this->getHeight() != $height) {
            if (!$forced) {
                $w_rate = $this->getWidth() / $width;
                $h_rate = $this->getHeight() / $height;

                $rate = ($w_rate > $h_rate) ? $w_rate : $h_rate;

                $width = round($this->getWidth() / $rate);
                $height = round($this->getHeight() / $rate);
            }

            $this->setWidthOfOut($width);
            $this->setHeightOfOut($height);

            if (self::$usableOfImagick) {
                $this->setCorrect();//2017-08-17 Lion: 轉正需要在 resize 之前

                $this->imagick->resizeImage($this->getWidthOfOut(), $this->getHeightOfOut(), \Imagick::FILTER_CATROM, 1);
            }
        }

        return $this;
    }

    /**
     * 設置類型
     * @param $type
     * @return $this
     */
    function setType($type)
    {
        if ($this->path === null) goto _return;

        //如果 type 有更動，留意 imagick->setImageFormat 的部分也要處理
        switch ($s_type = strtolower($type)) {
            case 'gif':
                $type = 1;
                break;

            case 'jpg':
            case 'jpeg':
                $type = 2;
                break;

            case 'png':
                $type = 3;
                break;

            case 'bmp':
                $type = 6;
                break;

            default:
                \model\log::setException(\lib\exception::LEVEL_NOTICE, 'Unknown case. "' . $s_type . '" of type by "' . $this->pathOfOut . '".');
                break;
        }

        $this->setTypeOfOut($type);

        if (self::$usableOfImagick) {
            $this->imagick->setImageFormat($this->type_correspond[$this->typeOfOut]);
        }

        _return:

        return $this;
    }

    /**
     * 設置輸出的類型
     * @param $type
     */
    protected function setTypeOfOut($type)
    {
        $this->typeOfOut = $type;
    }

    /**
     * 設置輸出的高度
     * @param $height
     */
    protected function setHeightOfOut($height)
    {
        $this->heightOfOut = $height;
    }

    /**
     * 設置輸出的寬度
     * @param $width
     */
    protected function setWidthOfOut($width)
    {
        $this->widthOfOut = $width;
    }
}