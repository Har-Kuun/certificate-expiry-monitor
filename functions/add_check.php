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

function add_domain_check($id,$visitor_ip) {
    global $current_domain;
    global $current_link;
    global $pre_check_file;
    global $check_file;
    $result = array();

    $pre_check_json_file = file_get_contents($pre_check_file);
    if ($file === FALSE) {
        $result['errors'][] = "无法打开数据库。";
        return $result;
    }
    $pre_check_json_a = json_decode($pre_check_json_file, true);
    if ($pre_check_json_a === null && json_last_error() !== JSON_ERROR_NONE) {
        $result['errors'][] = "无法读取数据库: " . htmlspecialchars(json_last_error());
        return $result;
    }

    if (!is_array($pre_check_json_a[$id]) ) {
      $result['errors'][] = "无法在数据库中找到该记录: " . htmlspecialchars($id);
        return $result;
    }

    $file = file_get_contents($check_file);
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
      if ($key == $id) {
          $result['errors'][] = "该域名/邮箱组合  " . htmlspecialchars($pre_check_json_a[$id]['domain']) . " 已存在。";
          return $result;
      }
      if ($value["domain"] == $pre_check_json_a[$id]['domain'] && $value["email"] == $pre_check_json_a[$id]['email']) {
          $result['errors'][] = "该域名/邮箱组合  " . htmlspecialchars($pre_check_json_a[$id]['domain']) . " 已存在。";
          return $result;
      }
    }

    $domains = validate_domains($pre_check_json_a[$id]['domain']);
    if (count($domains['errors']) >= 1 ) {
      $result['errors'][] = $domains['errors'];
      return $result;
    } 

    $json_a[$id] = array("domain" => $pre_check_json_a[$id]['domain'],
        "email" => $pre_check_json_a[$id]['email'],
        "errors" => 0,
        "visitor_pre_register_ip" => $pre_check_json_a[$id]['visitor_pre_register_ip'],
        "pre_add_date" => $pre_check_json_a[$id]['pre_add_date'],
        "visitor_confirm_ip" => $visitor_ip,
        "confirm_date" => time());

    $json = json_encode($json_a); 
    if(file_put_contents($check_file, $json, LOCK_EX)) {
        $result['success'][] = true;
    } else {
        $result['errors'][] = "无法写入数据库。";
        return $result;
    }

    unset($pre_check_json_a[$id]);
    $pre_check_json = json_encode($pre_check_json_a); 
    if(file_put_contents($pre_check_file, $pre_check_json, LOCK_EX)) {
        $result['success'][] = true;
    } else {
        $result['errors'][] = "无法写入数据库。";
        return $result;
    }

    $unsublink = "https://" . $current_link . "/unsubscribe.php?id=" . $id;

    $to      = $json_a[$id]['email'];
    $subject = "网站证书过期检测提醒已确认: " . htmlspecialchars($json_a[$id]['domain']) . ".";
    $message = "您好,

我们已收到您申请使用我们的证书过期检测提醒服务的请求，并且我们已经确认了您的网站。
  
域名   : " . trim(htmlspecialchars($json_a[$id]['domain'])) . "
邮箱   : " . trim(htmlspecialchars($json_a[$id]['email'])) . "
IP地址 : " . htmlspecialchars($visitor_ip) . "
日期   : " . date("Y-m-d H:i:s T") . "

我们将为您检测该网站的证书。您将在证书即将过期时收到我们的邮件提醒。您可以点击以下链接查看常见问题: https://" . $current_link . ".

如果您不再希望收到我们的邮件提醒，可以点击下面的链接取消订阅: 

  " . $unsublink . "

祝您健康顺利!
网站证书过期检测提醒 by 香菇肥牛
https://" . $current_link . "";
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
    }   else {
        echo("<p>邮件发送成功！</p>");
        return true;
    }


    return $result;
}
