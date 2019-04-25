<?php
/**
 * html 產生器
 * 1. 直接寫的 js 要在 return 時輸出, 因為  dynamictable 會將輸出的 js 做處理, 而引用的 js 就使用 set_js
 * 2. 為了配合 dynamictable 能處理各函式輸出的 js、css, 所以採用 retrun array($html, $js);
 * 3. 不在 html 裡寫 onclick 而獨立出來, 是因為 dynamictable 會用 js 處理輸出的 html, 此時 onclick 的值在單雙引號的跳脫處理就顯得麻煩
 * 4. 必須要這樣 $("body").on(events [, selector ]); (推測不一定要 body, 一個已存在且不會變動的父元素即可), 才能讓 dynamictable 產生的 html 也綁到事件
 * 5. 改為 element[id=\'xxx\'] 是因為當 id 有特殊字元時需要跳脫處理, 而以此寫法則不用, 參考 http://stackoverflow.com/questions/8404037/jquery-escape-square-brackets-to-select-element
 * 6. 將元素平行、垂直置中目前最佳做法
 *     (1) 將欲置中元素的上層元素 css 加上
 *         xxx:before {
 *             content: "";
 *             display: inline-block;
 *             height: inherit;
 *             vertical-align: middle;
 *         }
 *     (2) 將欲置中元素 css 加上
 *         xxx {
 *             display: inline-block;
 *             text-align: center;
 *             vertical-align: middle;
 *             width: inherit;
 *         }
 * 7. 不要在 js 碼裡寫 // 備註, 避免 dynamictable 異常
 * 8. static $pass 裡的 css、js 就直接給 static $css、$js, 避免 dynamictable 重複寫入 dom
 * @author lion
 */

namespace lib;

class html
{
    private static
        $css_file = [],
        $js_file = [];

    static function getCSS()
    {
        $return = null;

        if (self::$css_file) {
            $css_file = array_unique(self::$css_file);

            foreach ($css_file as $v_0) {
                $return .= '<link rel="stylesheet" type="text/css" href="' . $v_0 . '">';
            }
        }

        return $return;
    }

    static function getJS()
    {
        $return = null;

        if (self::$js_file) {
            $js_file = array_unique(self::$js_file);

            foreach ($js_file as $v_0) {
                $return .= '<script src="' . $v_0 . '"></script>';
            }
        }

        return $return;
    }

    function setCSS($file)
    {
        self::$css_file[] = $file;

        return $this;
    }

    function setJS($file)
    {
        self::$js_file[] = $file;

        return $this;
    }
}