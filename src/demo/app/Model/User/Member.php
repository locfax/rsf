<?php

namespace Model\User;

//用户model
class Member {

    use \Rsf\Traits\Singleton;

    public function get_by_id($uid) {
        return \Rsf\Db::findOne('member', '*', ['uid' => $uid]);
    }

    public function get_by_name($username) {
        return \Rsf\Db::findOne('member', '*', ['username' => $username]);
    }

    public function get_by_email($email) {
        return \Rsf\Db::findOne('member', '*', ['email' => $email]);
    }

    public function get_profile($uid) {
        return \Rsf\Db::findOne('member_profile', '*', ['uid' => $uid]);
    }

    public function get_status($uid) {
        return \Rsf\Db::findOne('member_stats', '*', ['uid' => $uid]);
    }

    public function check_name_format($username) {
        $guestexp = '\xA1\xA1|\xAC\xA3|^Guest|^\xD3\xCE\xBF\xCD|\xB9\x43\xAB\xC8';
        $len = strlen($username);
        if ($len > 15 || $len < 2 || preg_match("/\s+|^c:\\con\\con|[%,\*\"\s\<\>\&]|$guestexp/is", $username)) {
            return false;
        }
        return true;
    }

    public function check_name_exist($username) {
        return \Rsf\Db::count('member', "username = '{$username}'");
    }

    public function check_email_format($email) {
        return strlen($email) > 6 && preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email);
    }

    public function check_email_exist($email, $username = '') {
        $sqladd = ('' !== $username) ? "AND username <> '$username'" : '';
        return \Rsf\Db::count('member', "email = '{$email}' {$sqladd}");
    }

    public function check_email_access($email) {
        $setting = \Model\Setting::getInstance()->get_setting(['accessemail', 'censoremail']);
        if (!$setting) {
            return true;
        }
        $accessemail = $setting['accessemail'];
        $censoremail = $setting['censoremail'];
        $accessexp = '/(' . str_replace("\r\n", '|', preg_quote(trim($accessemail), '/')) . ')$/i';
        $censorexp = '/(' . str_replace("\r\n", '|', preg_quote(trim($censoremail), '/')) . ')$/i';
        if ($accessemail || $censoremail) {
            if (($accessemail && !preg_match($accessexp, $email)) || ($censoremail && preg_match($censorexp, $email))) {
                return false;
            }
        }
        return true;
    }

    public function check_login($username, $password) {
        $user = $this->get_by_name($username);
        if (empty($user['username'])) {
            $status = -1;
        } elseif ($user['password'] != topassword($password, $user['salt'])) {
            $status = -2;
        } elseif (1 != $user['status']) {
            $status = -3;
        } else {
            $status = 1;
        }
        return ['errcode' => $status, 'data' => $user];
    }

    public function record_login($uid) {
        \Rsf\Db::update('member_stats', "lastip ='" . clientip() . "',lastactivity = '" . time() . "',loginnum = loginnum +1", "uid = {$uid}");
    }

    public function add_user($post, $profile = []) {
        if (!isset($post['salt']) || !$post['salt']) {
            $post['salt'] = substr(md5(uniqid(rand())), -6);
        }
        if ($post['password']) {
            $post['password'] = topassword($post['password'], $post['salt']);
        }
        $uid = \Rsf\Db::create('member', $post, true);
        if ($uid) {
            $profile['uid'] = $uid;
            \Rsf\Db::create('member_profile', $profile);
            $post = [
                'uid' => $uid,
                'regip' => clientip()
            ];
            \Rsf\Db::create('member_stats', $post);
        }
        return $uid;
    }

    public function edit_user($uid, $post, $profile = [], $oldpw = '', $ignoreoldpw = true, $questionid = null, $answer = '') {
        $data = $this->get_by_id($uid);
        if (!$ignoreoldpw && $data['password'] != topassword($oldpw, $data['salt'])) {
            return 2;
        }
        if (isset($post['password'])) {
            if ($post['password']) {
                if (!$data['salt']) {
                    $post['salt'] = substr(md5(uniqid(rand())), -6);
                } else {
                    $post['salt'] = $data['salt'];
                }
                $post['password'] = topassword($post['password'], $post['salt']);
            } else {
                unset($post['password']);
            }
        }
        if (!is_null($questionid)) {
            $secques = $questionid > 0 ? $this->quescrypt($questionid, $answer) : '';
            $post['secques'] = $secques;
        }
        !empty($profile) && \Rsf\Db::update('member_profile', $profile, ['uid' => $uid]);
        $ret = \Rsf\Db::update('member', $post, ['uid' => $uid]);
        return $ret ? 1 : 0;
    }

    public function del_user($uid) {
        $ret = \Rsf\Db::remove('member', "uid={$uid}");
        if ($ret) {
            \Rsf\Db::remove('member_profile', "uid={$uid}");
            \Rsf\Db::remove('member_stats', "uid={$uid}");
        }
        return $ret;
    }

    public function quescrypt($questionid, $answer) {
        return $questionid > 0 && ('' !== $answer) ? substr(md5($answer . md5($questionid)), 16, 8) : '';
    }

    public function groups() {
        return \Rsf\Db::findAll('member_group', '*', '1 ORDER BY scores ASC');
    }
}
