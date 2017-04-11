<?php

namespace Model\File;

//loc 模式

class LocFile {

    use \Rsf\Traits\Singleton;

    public function check($_files, $fileField, $isOne = true, $filesite = false, $prefix = null) {
        //文件检测
        $uploader = \Rsf\Helper\Uploader::getInstance()->init($_files);
        $imageExt = getini('settings/imgext'); //允许的图片格式
        $imageMax = getini('setttings/imgmax'); //图片大小限制
        $fileExt = getini('settings/fileext'); //允许的文件格式
        $fileMax = getini('settings/filemax'); //文件大小限制

        if ($filesite) {
            $_filesite = $filesite;
        } else {
            $_filesite = getini('file/site'); //文件默认站点
        }

        if (is_null($prefix)) {
            $_prefix = $this->_make_prefix($_filesite);
        } else {
            $_prefix = $prefix;
        }

        $ERROR = '';
        $_filedir = getini("file/{$_filesite}/dir");
        if (!$_filedir) {
            $ERROR = '路径错误' . $_filedir;
            return ['flag' => false, 'msg' => $ERROR];
        }
        $allowfile = [
            'image' => explode(',', $imageExt),
            'imagemax' => intval($imageMax) * 1024,
            'file' => explode(',', $fileExt),
            'filemax' => intval($fileMax) * 1024,
        ];

        do {
            if ($isOne) {
                if (!$uploader->isFileExist($fileField)) {
                    $ERROR = "源文件不存在";
                    break;
                }
                $file = $uploader->getFile($fileField);
                if (!$file) {
                    $ERROR = "源文件获取失败";
                    break;
                }
                $ext = $file->getExt();
                $isimage = 0;
                if (in_array($ext, $allowfile['image'])) {
                    $isimage = 1;
                    $path = 'image';
                    $allowExt = implode(',', $allowfile['image']);
                    $allowMax = $allowfile['imagemax'];
                } elseif (in_array($ext, $allowfile['file'])) {
                    $path = 'media';
                    $allowExt = implode(',', $allowfile['file']);
                    $allowMax = $allowfile['filemax'];
                } else {
                    $ERROR = '上传文件格式错误';
                    break;
                }
                if (!$file->check($allowExt, $allowMax)) {
                    $ERROR = "上传文件大小必须小于" . $allowMax / (1024 * 1024) . "MB";
                    break;
                }
                return ['flag' => true, 'file' => $file, 'isimage' => $isimage, 'path' => $path, 'prefix' => $_prefix, 'filesite' => $_filesite];
            } else {
                $files = $uploader->getFiles();
                if (empty($files)) {
                    $ERROR = "源文件不存在";
                    break;
                }
                $retfile = ['flag' => true];
                $truefile = [];
                $falsefile = [];
                foreach ($files as $file) {
                    $isimage = 0;
                    $path = 'media';
                    $ext = $file->getExt();
                    $passd = true;
                    if (in_array($ext, $allowfile['image'])) {
                        $isimage = 1;
                        $path = 'image';
                        $allowExt = implode(',', $allowfile['image']);
                        $allowMax = $allowfile['imagemax'];
                    } elseif (in_array($ext, $allowfile['file'])) {
                        $allowExt = implode(',', $allowfile['file']);
                        $allowMax = $allowfile['filemax'];
                    } else {
                        $passd = false;
                    }
                    if (!$passd) {
                        $falsefile[] = $file->getFileName();
                        continue;
                    }
                    if (!$file->check($allowExt, $allowMax)) {
                        $falsefile[] = $file->getFileName();
                        continue;
                    }
                    $truefile[] = ['file' => $file, 'isimage' => $isimage, 'path' => $path, 'prefix' => $_prefix, 'filesite' => $_filesite];
                }
                $retfile['false'] = $falsefile;
                $retfile['true'] = $truefile;
                return $retfile;
            }
        } while (false);
        return ['flag' => false, 'msg' => $ERROR];
    }

    //上传文件
    public function upload(array $objfile, $filename = '', $mode = []) {
        $_newname = $filename;
        $path = $objfile['path'];
        $prefix = $objfile['prefix'];
        $filesite = $objfile['filesite'];
        $file = $objfile['file'];

        $fileopt = \Rsf\Helper\File::getInstance();

        if ($prefix) {
            $_fileDir = getini("file/{$filesite}/dir") . $path . '/' . $prefix;
        } else {
            $_fileDir = getini("file/{$filesite}/dir") . $path;
        }
        if (!$fileopt->mk_dir($_fileDir)) {
            return false; //生成路径
        }

        $default_mode = ['local' => 0, 'after' => 'unlink'];
        if (!empty($mode)) {
            $mode = array_merge($default_mode, $mode);
        } else {
            $mode = $default_mode;
        }
        if ($mode['local']) {
            //文件已经在服务器上 比如FTP 或者客服端 已经上传过的大文件
            $pathinfo = pathinfo($file);
            $_filename = $pathinfo['filename']; //上传文件名
            $finfo = new \finfo(FILEINFO_MIME);
            if (is_object($finfo)) {
                $mime = $finfo->file($file);
            } else {
                $mime = $this->_mime($file);
            }
            $size = filesize($file); //上传文件大小
            $ext = strtolower($pathinfo['extension']);
            if (!$_newname) {
                $_newname = $this->_makename($filesite, $objfile['path']) . '.' . $ext;
            }
            $_file = $_fileDir . '/' . $_newname;
            $flag = copy($file, $_file); //只复制
        } else {
            //上传文件
            $_filename = $file->getFilename(); //上传文件名
            $mime = $file->getMimeType(); //上传文件MIME头
            $size = $file->getSize(); //上传文件大小
            $ext = $file->getExt(); //上传文件扩展名
            if (!$_newname) {
                $_newname = $this->_makename($filesite, $objfile['path']) . '.' . $ext;
            }
            $_file = $_fileDir . '/' . $_newname;
            if ('unlink' == $mode['after']) {
                $flag = $file->move($_file);
            } else {
                $flag = $file->copy($_file);
            }
        }

        if ($prefix) {
            $filepath = $path . '/' . $prefix . '/' . $_newname;
        } else {
            $filepath = $path . '/' . $_newname;
        }
        return ['flag' => $flag, 'sourcename' => $_filename, 'newname' => $_newname, 'filepath' => $filepath, 'filesize' => $size];
    }

    //生成缩略图
    public function thumb(array $objfile, $filename = '', $whg = [], $mode = []) {
        $_newname = $filename;
        $path = $objfile['path'];
        $prefix = $objfile['prefix'];
        $filesite = $objfile['filesite'];
        $file = $objfile['file'];

        $fileopt = \Rsf\Helper\File::getInstance();

        if ($prefix) {
            $_fileDir = getini("file/{$filesite}/dir") . $path . '/' . $prefix . '/';
        } else {
            $_fileDir = getini("file/{$filesite}/dir") . $path . '/';
        }
        if (!$fileopt->mk_dir($_fileDir)) {
            return false;
        }
        $default_mode = ['mark' => 0, 'local' => false, 'imghandle' => 'imagegd', 'after' => ''];
        if (!empty($mode)) {
            $mode = array_merge($default_mode, $mode);
        } else {
            $mode = $default_mode;
        }
        if ($mode['local']) {
            //文件已经在服务器上 比如FTP 或者客服端 已经上传过的大文件
            $pathinfo = pathinfo($file);
            $tempname = $file;
            $ext = strtolower($pathinfo['extension']);
            if (!$_newname) {
                $_newname = $this->_makename($filesite, $path) . '.' . $ext;
            }
        } else {
            //上传文件
            $tempname = $file->getTmpName();
            $ext = $file->getExt();
            if (!$_newname) {
                $_newname = $this->_makename($filesite, $path) . '.' . $ext;
            }
        }
        if ($prefix) {
            $filepath = $path . '/' . $prefix . '/' . $_newname;
        } else {
            $filepath = $path . '/' . $_newname;
        }
        $imagehelper = '\\Rsf\\Helper\\' . ucfirst($mode['imghandle']);
        if (!empty($whg) && is_array($whg)) {
            $WHgroup = $whg;
        } else {
            $WHgroup = [['min', '150', '150', 'crop']];
        }
        //缩略图组
        $biglast = $bigwidth = 0;
        foreach ($WHgroup as $WH) {
            $imghandle = $imagehelper::createFromFile($tempname, $ext);
            $plus = $WH[0];
            $width = $WH[1];
            $height = $WH[2];
            $handfn = $WH[3];
            if ('crop' == $handfn) {
                $imghandle->crop($width, $height, ['fullimage' => true, 'enlarge' => false, 'reduce' => true]);
            } elseif ('resize' == $handfn) {
                $imghandle->resize($width, $height);
            } elseif ('autoresize' == $handfn) {
                $imghandle->autoresize($width, $height);
            } elseif ('resampled' == $handfn) {
                $imghandle->resampled($width, $height);
            } elseif ('canvas' == $handfn) {
                $imghandle->canvas($width, $height, 'center', '0xffffff');
            } else {
                $imghandle->crop($width, $height, ['fullimage' => true, 'enlarge' => true, 'reduce' => true]);
            }
            $newfile = $_fileDir . $_newname . '.' . $plus . '.jpg';
            $imghandle->saveAsJpeg($newfile, 100);
            if ($width > $biglast) {
                $bigwidth = $plus;
                $biglast = $width;
            }
            $imghandle = null;
        }
        //大图水印否
        if ($bigwidth && $bigwidth > 150 && $mode['mark']) {
            $newfile = $_fileDir . $_newname . '.' . $bigwidth . '.jpg';
            if (is_file($newfile)) {
                $markfile = $_fileDir . 'water.png';
                \Rsf\Helper\WaterMark::getInstance()->mark($newfile, 9, $markfile, '水印');
            }
        }
        if ('unlink' == $mode['after']) {
            $file->remove();
        }
        return ['newname' => $_newname, 'filepath' => $filepath];
    }

    private function _make_prefix($filesite) {
        //分类/年月/日为路径，方便做热门图移动
        $pfix = getini('file/' . $filesite . '/pfix');
        if ($pfix) {
            $prefix = date($pfix);
        } else {
            $prefix = '';
        }
        return $prefix;
    }

    private function _makename($filesite, $path) {
        if ('image' == $path) {
            $_path = 'i';
        } elseif ('thumb' == $path) {
            $_path = 't';
        } else {
            $_path = 'f';
        }
        $ffix = getini('file/' . $filesite . '/ffix');
        $ret = $_path . md5(microtime(true) . getmypid() . rand());
        if ($ffix) {
            $ret = date($ffix) . $ret;
        }
        return $ret;
    }

    private function _mime($filename) {
        $mime_types = [
            //text html
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',
            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',
            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        ];

        $ext = strtolower(array_pop(explode('.', $filename)));
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } else {
            return 'application/octet-stream';
        }
    }

}
