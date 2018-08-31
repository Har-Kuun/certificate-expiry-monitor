<?php
// Copyright (C) 2015 Remy van Elst

// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.

// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT);

set_include_path("." . PATH_SEPARATOR . ($UserDir = dirname($_SERVER['DOCUMENT_ROOT'])) . "/pear/php" . PATH_SEPARATOR . get_include_path());
require_once "Mail.php";

function add_domain_to_pre_check($domain,$email,$visitor_ip) {
    global $current_domain;
    global $current_link;
    global $pre_check_file;
    global $check_file;
    $result = array();
    $domain = trim($domain);
    $email = trim($email);
    $file = file_get_contents($pre_check_file);
    if ($file === FALSE) {
        $result['errors'][] = "无法打开数据库。";
        return $result;
    }
    $json_a = json_decode($file, true);
    if ($json_a === null && json_last_error() !== JSON_ERROR_NONE) {
        $result['errors'][] = "无法读取数据库: " . htmlspecialchars(json_last_error());
        return $result;
    }

    foreach ($json_a as $key => $value) {
        if ($value["domain"] == $domain && $value["email"] == $email) {
            $result['errors'][] = "域名/邮件组合  " . htmlspecialchars($domain) . " 已经存在。请在您的邮箱中点击确认链接。";
            return $result;
        }
    }

    $check_json_file = file_get_contents($check_file);
    if ($check_json_file === FALSE) {
        $result['errors'][] = "无法打开数据库。";
        return $result;
    }
    $check_json_a = json_decode($check_json_file, true);
    if ($check_json_a === null && json_last_error() !== JSON_ERROR_NONE) {
        $result['errors'][] = "无法读取数据库: " . htmlspecialchars(json_last_error());
        return $result;
    }

    foreach ($check_json_a as $key => $value) {
        if ($value["domain"] == $domain && $value["email"] == $email) {
            $result['errors'][] = "域名/邮件组合  " . htmlspecialchars($domain) . " 已经存在。";
            return $result;
        }
    }

    $uuid = gen_uuid();

    $json_a[$uuid] = array("domain" => $domain,
        "email" => $email,
        "visitor_pre_register_ip" => $visitor_ip,
        "pre_add_date" => time());

    $json = json_encode($json_a); 
    if(file_put_contents($pre_check_file, $json, LOCK_EX)) {
        $result['success'][] = true;
    } else {
        $result['errors'][] = "无法写入数据库。";
        return $result;
    }

    $sublink = "https://" . $current_link . "/confirm.php?id=" . $uuid;

    $to      = $email;
    $subject = "请确认域名 " . htmlspecialchars($domain) . " 的网站证书过期提醒";
    $message = "您好，\r\n\r\n您申请使用网站证书过期检测提醒服务。\r\n\r\n请点击链接确认该服务。如果您没有申请过我们的服务，请无视这封邮件。\r\n\r\n\r\n域名: " . trim(htmlspecialchars($domain)) . "\r\n邮箱: " . trim(htmlspecialchars($email)) . "\r\nIP地址: " . htmlspecialchars($visitor_ip) . "\r\n日期: " . date("Y-m-d H:i:s T") . "\r\n\r\n请点击下面的链接确认使用我们的服务: \r\n\r\n" . $sublink . "\r\n\r\n\r\n祝您健康愉快,\r\n网站证书过期检测提醒 by 香菇肥牛";
    $message = wordwrap($message, 70, "\r\n");
    $host = "ssl://smtp.sendgrid.net";
    $username = "apikey";
    $password = "xx.xxxxx";
    $port = "465";
    $email_from = "noreply@example.com";
    $replyto_address = "noreply@example.com";
    $headers = array ('From' => $email_from, 'To' => $to, 'Subject' => $subject, 'Reply-To' => $replyto_address, 'Content-Type'  => 'text/html; charset=UTF-8', 'X-Visitor-IP' => $visitor_ip, 'List-Unsubscribe' => $unsublink);

    $smtp = Mail::factory('smtp', array ('host' => $host, 'port' => $port, 'auth' => true, 'username' => $username, 'password' => $password));
    $mail = $smtp->send($to, $headers, $message);

    if (PEAR::isError($mail)) {
      echo("<p>邮件发送失败 " . $mail->getMessage() . "</p>");
      return false;
    } else {
      echo("<p>邮件发送成功！</p>");
      return true;
    }

    return $result;
}
