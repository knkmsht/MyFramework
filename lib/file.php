<?php

namespace lib;

class file
{
    static
        $upload_error_mapping = [
        0 => 'There is no error, the file uploaded with success.',
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
        3 => 'The uploaded file was only partially uploaded.',
        4 => 'No file was uploaded.',
        6 => 'Missing a temporary folder.',
        7 => 'Failed to write file to disk.',
        8 => 'A PHP extension stopped the file upload.',
    ],
        $upload_path,
        $upload_temporary_path;

    static function ableToUpload($name, array $allowed)
    {
        $check = function ($file, $allowed) {
            if (!is_writable(mkdir_p(self::getUploadPath()))) {
                $result = \lib\result::SYSTEM_ERROR;
                $message = '資料夾不可寫入。';
                $data = null;

                goto _return;
            }

            if (!is_writable(mkdir_p(self::getUploadTemporaryPath()))) {
                $result = \lib\result::SYSTEM_ERROR;
                $message = '暫存資料夾不可寫入。';
                $data = null;

                goto _return;
            }

            if ($file['error'] != UPLOAD_ERR_OK) {
                $result = \lib\result::SYSTEM_ERROR;
                $message = \lib\file::$upload_error_mapping[$file['error']];
                $data = null;

                goto _return;
            }

            if (!is_uploaded_file($file['tmp_name'])) {
                $result = \lib\result::SYSTEM_ERROR;
                $message = '檔案未經由正常途徑上傳。';
                $data = null;

                goto _return;
            }

            $extension = \lib\file::getExtension($file['tmp_name']);

            if (!isset($allowed[$extension])) {
                $result = \lib\result::SYSTEM_ERROR;
                $message = '不允許的檔案類型。';
                $data = [
                    'name' => $file['name'],
                    'type' => $file['type'],
                ];

                goto _return;
            } else {
                if (empty($allowed[$extension])) {
                    $result = \lib\result::SYSTEM_ERROR;
                    $message = '檔案類型 "' . $extension . '" 未限制檔案大小。"';
                    $data = [
                        'name' => $file['name'],
                        'type' => $file['type'],
                    ];

                    goto _return;
                } else {
                    if (toByte($allowed[$extension]) < $file['size']) {
                        $result = \lib\result::SYSTEM_ERROR;
                        $message = '"' . $file['name'] . '" 超過限制檔案大小 ' . $allowed[$extension] . '。';
                        $data = [
                            'name' => $file['name'],
                            'type' => $file['type'],
                        ];

                        goto _return;
                    }
                }
            }

            $result = \lib\result::SYSTEM_OK;
            $message = null;
            $data = null;

            _return:

            return return_encode($result, $message, $data);
        };

        if (!isset($_FILES[$name])) {
            $response = return_encode(\lib\result::SYSTEM_ERROR, 'Input name invalid.');

            goto _return;
        }

        switch (array_depth($_FILES[$name])) {
            case 1:
                $response = $check($_FILES[$name], $allowed);
                break;

            case 2:
                $array = reArrayFiles($_FILES[$name]);

                foreach ($array as $v_0) {
                    $response[] = $check($v_0, $allowed);
                }
                break;
        }

        _return:

        return $response;
    }

    /**
     * 複製檔案
     * a:1. 檔案陣列  2. 目標目錄  3. 是否覆蓋檔案
     * @回傳陣列格式:
     *  status = 1 / 0
     *  input = (int) 操作筆數
     *  output = (int) 執行筆數
     *  list = Array(str) 執行完成檔案
     *  fail = Array(str) 執行失敗檔案
     */
    static function copy(array $file, $target, $mode = false)
    {
        $return = array();
        //Default status = 0;
        $return['status'] = 0;

        if (!is_dir($target)) {
            $return['message'] = 'Directory not exist';
            return $return;
        }

        if (count($file) > 0) {
            $file_list = array();
            $file_fail = array();
            //enter array => status = 1;
            $return['status'] = 1;
            $return['input'] = count($file);
            foreach ($file as $v0) {
                $basename = end(explode('/', $v0));
                $filename = pathinfo($basename)['filename'];
                $extension = pathinfo($basename)['extension'];

                //不覆蓋
                if ($mode == false) {
                    if (file_exists($target . $basename)) {
                        $file_fail[] = $v0;
                        continue;
                    }
                }

                if (is_file($v0)) {
                    $file_list[] = $target . $basename;
                    copy($v0, end($file_list));
                } else {
                    $file_fail[] = $v0;
                }
            }
            $return['output'] = count($file_list);
            $return['list'] = $file_list;
            $return['fail'] = $file_fail;
        }
        return $return;
    }

    /**
     * 刪除檔案, 如果檔案為 image 則會將縮圖一併刪除
     * @param $file
     */
    static function delete($file)
    {
        $function = function ($file) {
            //2018-10-31 Lion: 在信任檔案置於 server 的把關前提下, 僅應對副檔名做處理
            $pathinfo = pathinfo($file);

            switch ($pathinfo['extension']) {
                case 'jpg':
                case 'jpeg':
                case 'png':
                    $return = glob($pathinfo['dirname'] . DIRECTORY_SEPARATOR . $pathinfo['filename'] . '*.' . $pathinfo['extension']);
                    break;

                default:
                    $return[] = $file;
                    break;
            }

            return $return;
        };

        $unlink = [];

        if (is_array($file)) {
            foreach (array_unique($file) as $v_0) {
                $unlink = array_merge($unlink, $function($v_0));
            }
        } else {
            $unlink = array_merge($unlink, $function($file));
        }

        foreach ($unlink as $v_0) {
            //2018-10-30 Lion: 由於傳入的路徑有可能不存在(需經過處理產生後綴, 如 avatar), 故找檔案不用 is_file, unlink 時才用
            if (is_file($v_0)) unlink($v_0);
        }
    }

    static function download($file, $basename = null)
    {
        if (!is_file($file)) throw new \Exception("[" . __METHOD__ . "] Parameters error");

        set_time_limit(0);

        $finfo = new \finfo();
        $filesize = filesize($file);
        $handle = fopen($file, 'rb');
        $basename = empty($basename) ? pathinfo($file, PATHINFO_BASENAME) : $basename;
        if (isset($_SERVER['HTTP_RANGE'])) {
            $http_range = explode('=', $_SERVER['HTTP_RANGE'])[1];
            $pos = strpos($http_range, '-');
            $http_range_start = substr($http_range, 0, $pos);
            $http_range_end = substr($http_range, $pos + 1);
            header('HTTP/1.1 206 Partial Content');
            header('Accept-Ranges: bytes');
            header('Content-Range: bytes ' . $http_range_start . '-' . $http_range_end . '/' . $filesize);
            header('Content-Length: ' . ($http_range_end - $http_range_start + 1));
            fseek($handle, $http_range_start);
        } else {
            $http_range_start = 0;
            $http_range_end = $filesize;
            header('HTTP/1.1 200 OK');
            header('Content-Length: ' . $filesize);
        }
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false);
        header('Content-Type: ' . $finfo->file($file, FILEINFO_MIME_TYPE));
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="' . $basename . '";');
        header('Content-Transfer-Encoding: binary');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT');
        ob_clean();
        flush();
        echo fread($handle, $http_range_end - $http_range_start + 1);
        fclose($handle);
    }

    static public function getExtension($file)
    {
        $extension = null;

        if (is_file($file)) {
            $mime = mime_content_type($file);

            $map = [
                'audio/mpeg' => 'mp3',
                'image/jpeg' => 'jpg'
            ];

            $extension = isset($map[$mime]) ? $map[$mime] : null;
        }

        return $extension;
    }

    static private function getUploadPath()
    {
        if (self::$upload_path === null) {
            self::$upload_path = \config\path::$Upload . date('Ymd') . DIRECTORY_SEPARATOR;
        }

        return self::$upload_path;
    }

    static private function getUploadTemporaryPath()
    {
        if (self::$upload_temporary_path === null) {
            self::$upload_temporary_path = \config\path::$Upload . 'temporary' . DIRECTORY_SEPARATOR;
        }

        return self::$upload_temporary_path;
    }

    /**
     * 重新命名檔案
     * a:1. 檔案陣列  2. 完整路徑+檔名陣列  3. 是否覆蓋檔案
     * b:避免錯誤，重命名路徑需等於原檔案路徑，否則視為搬移檔案而非重命名
     * @回傳陣列格式:
     *  status = 1 / 0
     *  input = (int) 操作筆數
     *  output = (int) 執行筆數
     *  list = Array(str) 執行完成檔案
     *  fail = Array(str) 執行失敗檔案
     */
    static function rename(array $file, array $target, $mode = false)
    {
        $return = array();
        //Default status = 0;
        $return['status'] = 0;

        if (!is_array($target)) return $return;

        if (count($file) == count($target)) {
            $file_list = array();
            $file_fail = array();
            //enter array => status = 1;
            $return['status'] = 1;
            $return['input'] = count($file);
            foreach ($file as $k0 => $v0) {
                $basename = end(explode('/', $v0));
                $filename = pathinfo($basename)['filename'];
                $extension = pathinfo($basename)['extension'];

                //覆蓋
                if ($mode == false && file_exists($target[$k0])) {
                    $file_fail[] = $v0;
                    continue;
                }
                //路徑需相同
                if (dirname($file[$k0]) != dirname($target[$k0])) {
                    $file_fail[] = $v0;
                    continue;
                }

                if (is_file($v0)) {
                    $file_list[] = $target[$k0];
                    rename($v0, $target[$k0]);
                } else {
                    $file_fail[] = $v0;
                }
            }
            $return['output'] = count($file_list);
            $return['list'] = $file_list;
            $return['fail'] = $file_fail;
        }
        return $return;
    }

    /**
     * 搬移檔案
     * a:1. 檔案陣列  2. 目標目錄  3. 是否覆蓋檔案
     * @回傳陣列格式:
     *  status = 1 / 0
     *  input = (int) 操作筆數
     *  output = (int) 執行筆數
     *  list = Array(str) 執行完成檔案
     *  fail = Array(str) 執行失敗檔案
     */
    static function move(array $file, $target, $mode = false)
    {
        $return = array();
        //Default status = 0;
        $return['status'] = 0;

        if (!is_dir($target)) {
            $return['message'] = 'Directory not exist';
            return $return;
        }

        if (count($file) > 0) {
            $file_list = array();
            $file_fail = array();
            //enter array => status = 1;
            $return['status'] = 1;
            $return['input'] = count($file);
            foreach ($file as $v0) {
                $basename = end(explode('/', $v0));
                $filename = pathinfo($basename)['filename'];
                $extension = pathinfo($basename)['extension'];

                //不覆蓋
                if ($mode == false) {
                    if (file_exists($target . $basename)) {
                        $file_fail[] = $v0;
                        continue;
                    }
                }

                if (is_file($v0)) {
                    $file_list[] = $target . $basename;
                    rename($v0, end($file_list));
                } else {
                    $file_fail[] = $v0;
                }
            }
            $return['output'] = count($file_list);
            $return['list'] = $file_list;
            $return['fail'] = $file_fail;
        }
        return $return;
    }

    static function moveToFormal($path)
    {
        $response = [];

        if (is_array($path)) {
            foreach (array_unique($path) as $v_0) {
                $newname = self::getUploadPath() . pathinfo($v_0, PATHINFO_BASENAME);

                if (file_exists($v_0)) rename($v_0, $newname);

                $response[$v_0] = $newname;
            }
        } else {
            $newname = self::getUploadPath() . pathinfo($path, PATHINFO_BASENAME);

            if (file_exists($path)) rename($path, $newname);

            $response[$path] = $newname;
        }

        _return:

        return $response;
    }

    static public function upload($name, array $allowed)
    {
        $check = function ($file, $allowed) {
            $upload_path = \config\path::$Upload . date('Ymd') . DIRECTORY_SEPARATOR;

            if (!is_writable(mkdir_p($upload_path))) {
                $result = \lib\result::SYSTEM_ERROR;
                $message = '檔案資料夾不可寫入。';
                $data = null;

                goto _return;
            }

            if ($file['error'] != UPLOAD_ERR_OK) {
                $result = \lib\result::SYSTEM_ERROR;
                $message = \lib\file::$upload_error_mapping[$file['error']];
                $data = null;

                goto _return;
            }

            if (!is_uploaded_file($file['tmp_name'])) {
                $result = \lib\result::SYSTEM_ERROR;
                $message = '檔案未經由正常途徑上傳。';
                $data = null;

                goto _return;
            }

            $extension = \lib\file::getExtension($file['tmp_name']);

            if (!isset($allowed[$extension])) {
                $result = \lib\result::SYSTEM_ERROR;
                $message = '不允許的檔案類型。';
                $data = [
                    'name' => $file['name'],
                    'type' => $file['type'],
                ];

                goto _return;
            } else {
                if (empty($allowed[$extension])) {
                    $result = \lib\result::SYSTEM_ERROR;
                    $message = '檔案類型 "' . $extension . '" 未限制檔案大小。"';
                    $data = [
                        'name' => $file['name'],
                        'type' => $file['type'],
                    ];

                    goto _return;
                } else {
                    if (toByte($allowed[$extension]) < $file['size']) {
                        $result = \lib\result::SYSTEM_ERROR;
                        $message = '"' . $file['name'] . '" 超過限制檔案大小 ' . $allowed[$extension] . '。';
                        $data = [
                            'name' => $file['name'],
                            'type' => $file['type'],
                        ];

                        goto _return;
                    }
                }
            }

            $path = $upload_path . uniqid() . '.' . $extension;

            move_uploaded_file($file['tmp_name'], $path);

            $result = \lib\result::SYSTEM_OK;
            $message = '上傳成功。';
            $data = [
                'name' => $file['name'],
                'path' => $path,
                'type' => $file['type'],
            ];

            _return:

            return return_encode($result, $message, $data);
        };

        if (!isset($_FILES[$name])) {
            $response = return_encode(\lib\result::SYSTEM_ERROR, 'Input name invalid.');

            goto _return;
        }

        switch (array_depth($_FILES[$name])) {
            case 1:
                $response = $check($_FILES[$name], $allowed);
                break;

            case 2:
                $array = reArrayFiles($_FILES[$name]);

                foreach ($array as $v_0) {
                    $response[] = $check($v_0, $allowed);
                }
                break;
        }

        _return:

        return $response;
    }

    static public function uploadToTemporary($name)
    {
        $uploadToTemporary = function ($file) {
            $path = self::getUploadTemporaryPath() . uniqid() . '.' . \lib\file::getExtension($file['tmp_name']);

            move_uploaded_file($file['tmp_name'], $path);

            $result = \lib\result::SYSTEM_OK;
            $message = '上傳成功。';
            $data = [
                'name' => $file['name'],
                'path' => $path,
                'type' => $file['type'],
            ];

            return return_encode($result, $message, $data);
        };

        switch (array_depth($_FILES[$name])) {
            case 1:
                $response = $uploadToTemporary($_FILES[$name]);
                break;

            case 2:
                $array = reArrayFiles($_FILES[$name]);

                foreach ($array as $v_0) {
                    $response[] = $uploadToTemporary($v_0);
                }
                break;
        }

        return $response;
    }
}