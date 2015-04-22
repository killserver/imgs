<?php

/**
 * @author      dostelon <dostelon@gmail.com> <icq:577366>
 * @project     pic2money
 * @version     1.0
 * @link        http://dostelon.ru/pic2money/
 */

include 'config.php';
//include EXTENSION . 'function.all.php';
include EXTENSION . 'function.geoip.php';
include EXTENSION . 'class.db.php';

$db = new Db("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASSWORD);

if(isset($_SERVER['HTTP_REFERER'])){
	$url = parse_url($_SERVER['HTTP_REFERER']);
}else{
	$url['host'] = 'imgmoney.ru';
}

$host = parse_url(HTTP);

$nopic = FALSE;



$sql = 'SELECT * FROM `pics` WHERE `pic_id` = :pic_id AND `pic_del` = 0';
$data = array(':pic_id' => $_GET['pic_id']);
$pic_info = $db->sql($sql, $data);

foreach ($ban as $key => $value) {
    if (isset($_GET['referer']) AND strpos(base64_decode($_GET['referer']), $value) != FALSE) {
		$full = ROOT . '/img/' . implode('/', str_split($pic_info['0']['pic_patch'])) . '/' . $pic_info['0']['pic_id'] . '.' . $pic_info['0']['pic_ext'];
		header('Content-Type: image/' . $pic_info['0']['pic_ext']);
        readfile($full);
        exit;
    }
}

if (md5($_SERVER['HTTP_USER_AGENT'] . getip() . $_GET['pic_id'] . $_GET['referer'] . SALT . $pic_info[0]['pic_cat']) == $_GET['sig'] AND $url['host'] == $host['host']) {
//if(true){


    if ($pic_info <> FALSE AND !$nopic) {

        $sql = 'SELECT * FROM `ips` WHERE `ip_ip` = :ip_ip AND `ip_unique` = 1 AND ip_time > :time';
        $data = array(':ip_ip' => getip(), ':time' => time() - 60 * 60 * 24);
        $ip_info = $db->sql($sql, $data);

        $cc = geoip(getip());



        //unique
        if ($ip_info === FALSE AND UNIQUE_SYSTEM AND $_GET['referer'] !== '' AND rand(0, 100000)>=50000) { //регулируя значение 50000 - добиваемся рандома в зачислении просмотра


            if (!isset($payment[$pic_info['0']['pic_cat']][$cc])) {
                $payment[$pic_info['0']['pic_cat']][$cc] = 0;
            }

            $sql = 'INSERT INTO ips(ip_user_id, ip_pic_id, ip_ip, ip_agent, ip_ref, ip_unique, ip_country,ip_money,ip_time)VALUES(:ip_user_id, :ip_pic_id, :ip_ip, :ip_agent, :ip_ref, :ip_unique, :ip_country,:ip_money,:ip_time)';
            $data = array(':ip_user_id' => $pic_info['0']['pic_user_id'], ':ip_pic_id' => $pic_info['0']['pic_id'], ':ip_ip' => getip(), ':ip_agent' => $_SERVER['HTTP_USER_AGENT'], ':ip_ref' => base64_decode($_GET['referer']), ':ip_unique' => 1, ':ip_country' => $cc, ':ip_money' => $payment[$pic_info['0']['pic_cat']][$cc], ':ip_time' => time());
            $db->sql($sql, $data);


            $sql = 'UPDATE `pics` SET pic_unique = pic_unique + 1, pic_money = pic_money + :pic_money, pic_lastclick = :pic_lastclick WHERE pic_id=:pic_id';
            $data = array(':pic_money' => $payment[$pic_info['0']['pic_cat']][$cc], ':pic_id' => $pic_info['0']['pic_id'], ':pic_lastclick' => time());
            $db->sql($sql, $data);

            $sql = 'UPDATE `users` SET user_clickmoney = user_clickmoney + :user_clickmoney WHERE user_id=:user_id';
            $data = array(':user_clickmoney' => $payment[$pic_info['0']['pic_cat']][$cc], ':user_id' => $pic_info['0']['pic_user_id']);
            $db->sql($sql, $data);
        } 


        $full = ROOT . '/img/' . implode('/', str_split($pic_info['0']['pic_patch'])) . '/' . $pic_info['0']['pic_id'] . '.' . $pic_info['0']['pic_ext'];

        header('Content-Type: image/' . $pic_info['0']['pic_ext']);

        readfile($full);

	

    } else {
        header('Content-Type: image/png');
        readfile(ROOT . "/nopic.png");
    }
}


