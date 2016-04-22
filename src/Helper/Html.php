<?php

namespace Rsf\Helper;

class Html {

    use \Rsf\Traits\Singleton;

    public function dropdown_list($name, $arr, $selected = null, $extra = null) {
        $str = "<select name=\"{$name}\" {$extra} >\n";
        foreach ($arr as $value => $title) {
            $str .= '<option value="' . input_text($value) . '"';
            if ($selected == $value) {
                $str .= ' selected';
            }
            $str .= '>' . input_text($title) . "&nbsp;&nbsp;</option>\n";
        }
        $str .= "</select>\n";
        return $str;
    }

    public function radio_group($name, $arr, $checked = null, $separator = '', $extra = null) {
        $ix = 0;
        $str = "";
        foreach ($arr as $value => $title) {
            $value_h = input_text($value);
            $title = input_text($title);
            $str .= "<input name=\"{$name}\" type=\"radio\" id=\"{$name}_{$ix}\" value=\"{$value_h}\" ";
            if ($value == $checked) {
                $str .= "checked=\"checked\"";
            }
            $str .= " {$extra} />";
            $str .= "<label for=\"{$name}_{$ix}\">{$title}</label>";
            $str .= $separator;
            $ix++;
            $str .= "\n";
        }
        return $str;
    }

    public function checkbox_group($name, $arr, $selected = [], $separator = '', $extra = null) {
        $ix = 0;
        if (!is_array($selected)) {
            $selected = [$selected];
        }
        $str = "";
        foreach ($arr as $value => $title) {
            $value_h = output_char($value);
            $title = output_char($title);
            $str .= "<input name=\"{$name}[]\" type=\"checkbox\" id=\"{$name}_{$ix}\" value=\"{$value_h}\" ";
            if (in_array($value, $selected)) {
                $str .= "checked=\"checked\"";
            }
            $str .= " {$extra} />";
            $str .= "<label for=\"{$name}_{$ix}\">{$title}</label>";
            $str .= $separator;
            $ix++;
            $str .= "\n";
        }
        return $str;
    }

    public function checkbox($name, $value = 1, $checked = false, $label = '', $extra = null) {
        $str = "<input name=\"{$name}\" type=\"checkbox\" id=\"{$name}_1\" value=\"{$value}\"";
        if ($checked) {
            $str .= " checked";
        }
        $str .= " {$extra} />\n";
        if ($label) {
            $str .= "<label for=\"{$name}_1\">" . input_text($label) . "</label>\n";
        }
        return $str;
    }

    public function textbox($name, $value = '', $width = null, $maxLength = null, $extra = null) {
        $str = "<input name=\"{$name}\" type=\"text\" value=\"" . input_text($value) . "\" ";
        if ($width) {
            $str .= "size=\"{$width}\" ";
        }
        if ($maxLength) {
            $str .= "maxlength=\"{$maxLength}\" ";
        }
        $str .= " {$extra} />\n";
        return $str;
    }

    public function password($name, $value = '', $width = null, $maxLength = null, $extra = null) {
        $str = "<input name=\"{$name}\" type=\"password\" value=\"" . input_text($value) . "\" ";
        if ($width) {
            $str .= "size=\"{$width}\" ";
        }
        if ($maxLength) {
            $str .= "maxlength=\"{$maxLength}\" ";
        }
        $str .= " {$extra} />\n";
        return $str;
    }

    public function textarea($name, $value = '', $width = null, $height = null, $extra = null) {
        $str = "<textarea name=\"{$name}\"";
        if ($width) {
            $str .= "cols=\"{$width}\" ";
        }
        if ($height) {
            $str .= "rows=\"{$height}\" ";
        }
        $str .= " {$extra} >";
        $str .= input_text($value);
        $str .= "</textarea>\n";
        return $str;
    }

    public function hidden($name, $value = '', $extra = null) {
        $str = "<input name=\"{$name}\" type=\"hidden\" value=\"";
        $str .= input_text($value);
        $str .= "\" {$extra} />\n";
        return $str;
    }

    public function filefield($name, $width = null, $extra = null) {
        $str = "<input name=\"{$name}\" type=\"file\"";
        if ($width) {
            $str .= " size=\"{$width}\"";
        }
        $str .= " {$extra} />\n";
        return $str;
    }

    public function form_open($name, $action, $method = 'post', $onsubmit = '', $extra = null) {
        $str = "<form name=\"{$name}\" action=\"{$action}\" method=\"{$method}\" ";
        if ($onsubmit) {
            $str .= "onsubmit=\"{$onsubmit}\"";
        }
        $str .= " {$extra} >\n";
        return $str;
    }

    public function form_close() {
        return "</form>\n";
    }

}
