<?php

namespace Rsf\Helper;

class Unzip {

    use \Rsf\Base\Singleton;

    function decombineFile(StringStream $build, $dir) {
        $s = $build->get(8);
        $dirlen = hexdec($s);
        $filename = $build->get($dirlen);
        echo "Extracting: $dir/$filename <br>";
        flush();
        ob_flush();
        $s = $build->get(8);
        $s = hexdec($s);
        $content = $build->get($s);
        //global $path;
        if (($f = fopen($dir . '/' . $filename, 'wb'))) {
            fwrite($f, $content);
        }
        return $dir . '/' . $filename;
    }

    function decombineDir($build, $dir) {
        $s = $build->get(8);
        $dirlen = hexdec($s);
        $dirname = $build->get($dirlen);
        echo "$dir/$dirname <br>";
        flush();
        ob_flush();
        if (!is_dir($dir . '/' . $dirname)) {
            echo "Making dir $dir/$dirname </br>";
            mkdir($dir . '/' . $dirname);
        }
        while ('o' != ($tp = $build->get())) {
            if ($tp == 'd') {
                echo "Extractiong directory: ";
                decombineDir($build, $dir . '/' . $dirname);
            } elseif ($tp == 'f') {
                echo "Extractiong file: ";
                decombineFile($build, $dir . '/' . $dirname);
            } else {
                die("tp:$tp");
            }
        }
        return $dir . '/' . $dirname;
    }

    function unzip($file) {

        //clearDir('./');
        $build = fopen($file, 'rb');
        if (!$build)
            return;
        $build = fread($build, filesize($file));
        $build = gzinflate($build);
        $ss = new StringStream($build);
        $ss->get();
        decombineDir($ss, './');
    }

}