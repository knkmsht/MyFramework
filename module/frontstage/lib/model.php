<?php

namespace frontstage;

class model
{
    /**
     * 處理 html 中的檔案
     * @param $html
     * @param null $past_html
     * @return mixed
     */
    static function handleFileInHtml($html, $past_html = null)
    {
        if (replaceSpace($html) !== '') {
            $array_unhandled_url = (new \lib\htmlparse)
                ->set($html)
                ->setTagName('img')
                ->setAttribute('src')
                ->get();

            $array_temporary_path = [];

            foreach ($array_unhandled_url as $url) {
                if (strpos($url, \config\url::$UploadTemporary) !== false) {
                    $array_temporary_path[] = url2path($url);
                }
            }

            $map_path = \lib\file::moveToFormal($array_temporary_path);

            $html = str_replace(
                array_map('path2url', array_keys($map_path)),
                array_map('path2url', array_values($map_path)),
                $html
            );
        }

        if (replaceSpace($past_html) !== '') {
            $array_past_url = array_unique(
                (new \lib\htmlparse)
                    ->set($past_html)
                    ->setTagName('img')
                    ->setAttribute('src')
                    ->get()
            );

            if ($array_past_url) {
                $array_url = array_unique(
                    (new \lib\htmlparse)
                        ->set($html)
                        ->setTagName('img')
                        ->setAttribute('src')
                        ->get()
                );

                \lib\file::delete(array_diff(
                    array_map('url2path', $array_past_url),
                    array_map('url2path', $array_url)
                ));
            }
        }

        return $html;
    }
}
