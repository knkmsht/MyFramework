<?php

namespace frontstage;

class controller
{
    protected static $data = [];
    protected static $view = [];
    protected static $seo = [];

    function __construct()
    {
        //maintain
        if (\model\settings::getByKeyword('SITE_MAINTAIN_SWITCH') && M_METHOD != '_::maintain' && empty(\lib\session::get('admin'))) {
            redirect(self::url('_', 'maintain'));
        }
    }

    static function display()
    {
        $include = function ($path, $variable) {
            foreach ($variable as $k_0 => $v_0) {
                $$k_0 = $v_0;
            }

            include $path;
        };

        $view_above = [
            \config\path::$FrontStage_View . 'head.phtml',
            \config\path::$FrontStage_View . 'headbar.phtml',
            \config\path::$FrontStage_View . 'popup.phtml',
        ];

        $view_below = [
            \config\path::$FrontStage_View . 'footbar.phtml',
            \config\path::$FrontStage_View . 'foot.phtml'
        ];

        //
        foreach (self::$view as $v_0) {
            foreach ($view_above as $v_1) {
                if ($v_0['phtml'] === $v_1 && is_file($v_0['phtml'])) {
                    $include($v_0['phtml'], $v_0['data']);
                }
            }
        }

        //
        foreach (self::$view as $v_0) {
            if (in_array($v_0['phtml'], $view_above, true) || in_array($v_0['phtml'], $view_below, true)) {
                continue;
            }

            if (is_file($v_0['phtml'])) $include($v_0['phtml'], $v_0['data']);
        }

        //
        foreach (self::$view as $v_0) {
            foreach ($view_below as $v_1) {
                if ($v_0['phtml'] === $v_1 && is_file($v_0['phtml'])) {
                    $include($v_0['phtml'], $v_0['data']);
                }
            }
        }
    }

    function setFoot($data = [])
    {
        (new \frontstage\html)
            ->setWYSIWYGEditor();

        $this
            ->setView(\config\path::$FrontStage_View . 'foot.phtml', $data);

        return $this;
    }

    function setFootBar()
    {
        $this
            ->setView(\config\path::$FrontStage_View . 'footbar.phtml');

        return $this;
    }

    function setHead()
    {
        (new \lib\html)
            ->setCSS(\config\url::$StaticFrontStageCSS . 'css.css');

        (new \frontstage\html)
            ->setjQuery()
            ->setBootstrap()
            ->setFontAwesome()
            ->setChart()
            ->setCropImage()
            ->setTextComplete()
            ->setjBox()
            ->setImageSlider()
            ->setInView()
            ->setTagify()
            ->setjQueryLoading()
            ->setJSSocials()
            ->setPHPJS();

        $this
            ->setView(
                \config\path::$FrontStage_View . 'head.phtml',
                [
                    'head' => \frontstage\html::getHeadContent(),
                ]
            );

        return $this;
    }

    function setHeadBar()
    {
        $s_user = \model\user::getSession();

        if ($s_user) {
            $user = [
                'avatar' => path2url(\model\user::getAvatar($s_user['user_id'], \config\image::s4)),
            ];
        }

        $data = [
            'user' => $user ?? null,
            'url_settings' => \frontstage\controller::url('settings'),
        ];

        if ($_GET['query'] ?? null) $data['query'] = $_GET['query'];

        $this
            ->setView(\config\path::$FrontStage_View . 'headbar.phtml', $data)
            ->setView(\config\path::$FrontStage_View . 'popup.phtml');

        return $this;
    }

    function setView($phtml = null, $data = [])
    {
        self::$view[] = [
            'phtml' => $phtml === null ? \config\path::$FrontStage_View . \config\module::$Class . DIRECTORY_SEPARATOR . \config\module::$Function . '.phtml' : $phtml,
            'data' => $data,
        ];

        return $this;
    }

    /**
     * @param null $class
     * @param null $function
     * @param array|null $param
     * @return string
     */
    static function url($class = null, $function = null, array $param = null)
    {
        $url = \config\url::$Root;

        if ($function != null && $function != \config\project::index) {
            $url .= $class . '/' . $function . '/';
        } elseif ($class != null && $class != \config\project::index) {
            $url .= $class . '/';
        }

        if (!empty($param)) {
            $url .= '?' . http_build_query($param, '', '&');
        }

        return $url;
    }
}
