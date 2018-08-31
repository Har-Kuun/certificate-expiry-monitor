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

function remove_domain_check($id,$visitor_ip) {
    global $current_domain;
    global $current_link;
    global $check_file;
    global $deleted_check_file;
    $result = array();

    $deleted_check_json_file = file_get_contents($deleted_check_file);
    if ($file === FALSE) {
        $result['errors'][] = "无法打开数据库。";
        return $result;
    }
    $deleted_check_json_a = json_decode($deleted_check_json_file, true);
    if ($deleted_check_json_a === null && json_last_error() !== JSON_ERROR_NONE) {
        $result['errors'][] = "无法读取数据库: " . htmlspecialchars(json_last_error());
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

    if (!is_array($json_a[$id]) ) {
      $result['errors'][] = "无法在数据库中找到该记录: " . htmlspecialchars($id);
        return $result;
    }

    foreach ($json_a as $key => $value) {
      if ($key == $id) {
        $deleted_json_a[$id] = array("domain" => $json_a[$id]['domain'],
            "email" => $json_a[$id]['email'],
            "visitor_pre_register_ip" => $json_a[$id]['visitor_pre_register_ip'],
            "pre_add_date" => $json_a[$id]['pre_add_date'],
            "visitor_confirm_ip" => $json_a[$id]['visitor_confirm_ip'],
            "confirm_date" => $json_a[$id]['confirm_date'],
            "visitor_delete_ip" => $visitor_ip,
            "delete_date" => time(),
            );

        $deleted_json = json_encode($deleted_json_a); 
        if(file_put_contents($deleted_check_file, $deleted_json, LOCK_EX)) {
            $result['success'][] = true;
        } else {
            $result['errors'][] = "无法写入数据库。";
            return $result;
        }

        unset($json_a[$id]);
        $check_json = json_encode($json_a); 
        if(file_put_contents($check_file, $check_json, LOCK_EX)) {
            $result['success'][] = true;
        } else {
            $result['errors'][] = "无法写入数据库。";
            return $result;
        }

        $link = "https://" . $current_link . "/";

        $to      = $deleted_json_a[$id]['email'];
        $subject = "域名 " . htmlspecialchars($deleted_json_a[$id]['domain']) . "的网站证书过期检测提醒服务已取消";
        $message = "您好，\r\n\r\n您的网站证书过期提醒服务已经取消。\r\n\r\n域名: " . trim(htmlspecialchars($deleted_json_a[$id]['domain'])) . "\r\n邮箱: " . trim(htmlspecialchars($deleted_json_a[$id]['email'])) . "\r\nIP地址: " . htmlspecialchars($visitor_ip) . "\r\n日期: " . date("Y-m-d H:i:s") . "\r\n\r\n我们将不再检测该网站的证书过期时间，您也不会再收到关于该网站的证书过期提醒。\r\n\r\n如果您想重新添加该域名，请访问我们的网站: \r\n\r\n  " . $link . "\r\n\r\n祝您健康愉快,\r\n网站证书过期检测提醒 by 香菇肥牛\r\nhttps://" . $current_link . "";
        $message = wordwrap($message, 70, "\r\n");
        $headers = 'From: noreply@' . $current_domain . "\r\n" .
            'Reply-To: noreply@' . $current_domain . "\r\n" .
            'Return-Path: noreply@' . $current_domain . "\r\n" .
            'X-Visitor-IP: ' . $visitor_ip . "\r\n" .
            'X-Coffee: Black' . "\r\n" .
            'Content-Type: text/html; charset=UTF-8' . "\r\n" .
            'List-Unsubscribe: <https://' . $current_link . "/unsubscribe.php?id=" . $id . ">" . "\r\n" .
            'X-Mailer: PHP/4.1.1';

        if (mail($to, $subject, $message, $headers) === true) {
            $result['success'][] = true;
        } else {
            $result['errors'][] = "无法发送邮件。";
            return $result;
        }
        return $result;
    }
  }
}
