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

function validate_email($email) {
  if (!filter_var(mb_strtolower($email), FILTER_VALIDATE_EMAIL)) {
    return false;
  } else {
    return true;
  }
}

function send_error_mail($domain, $email, $errors) {
  echo "\t\t发送错误信息邮件至 $email 域名 $domain.\n";
  global $current_domain;
  global $current_link;
  global $check_file;
  $domain = trim($domain);
  $errors = implode("\r\n", $errors);
  $json_file = file_get_contents($check_file);
  if ($check_file === FALSE) {
      echo "\t\t无法打开数据库。\n";
      return false;
  }
  $json_a = json_decode($json_file, true);
  if ($json_a === NULL || json_last_error() !== JSON_ERROR_NONE) {
      echo "\t\t无法读取数据库。\n";
      return false;
  }

  foreach ($json_a as $key => $value) {
    if ($value["domain"] == $domain && $value["email"] == $email) {
      $id = $key;
      $failures = $value['errors'];
      $unsublink = "https://" . $current_link . "/unsubscribe.php?id=" . $id;
      $to      = $email;
      $subject = "证书检测 " . htmlspecialchars($domain) . " failed.";
      $message = "您好,\r\n\r\n您之前申请了域名 " . htmlspecialchars($domain) . " 的网站证书检测服务。\r\n\r\n我们今天在检测您的域名时遇到了错误： \r\n\r\n域名: " . htmlspecialchars($domain) . "\r\n错误: " . htmlspecialchars($errors) . "\r\n\r\nFailure(s): " . htmlspecialchars($failures) . "\r\n\r\n请您检查该网站或证书的状态。如果我们连续七天检测到该网站证书的错误，我们将取消该域名的证书检测服务。若您在七天内恢复，错误检测的时间计数器将充值。\r\n\r\n如果您不想再收到我们的提醒邮件，请点击下面的链接取消订阅:\r\n\r\n" . $unsublink . "\r\n\r\n\r\n 祝您健康愉快,\r\n网站证书过期检测提醒 by 香菇肥牛\r\nhttps://" . $current_link . "";
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

    } 
  }
}

function send_cert_expired_email($days, $domain, $email, $raw_cert) {
  global $current_domain;
  global $current_link;
  global $check_file;
  $domain = trim($domain);
  echo "\t\tDomain " . $domain . " expired " . $days . " ago.\n";

  $file = file_get_contents($check_file);
  if ($file === FALSE) {
      echo "\t\t无法打开数据库。\n";
      return false;
  }
  $json_a = json_decode($file, true);
  if ($json_a === null && json_last_error() !== JSON_ERROR_NONE) {
      echo "\t\t无法读取数据库。\n";
      return false;
  }

  foreach ($json_a as $key => $value) {

    if ($value["domain"] == $domain && $value["email"] == $email) {

      $id = $key;
      $cert_cn = cert_cn($raw_cert);
      $cert_subject = cert_subject($raw_cert);
      $cert_serial = cert_serial($raw_cert);
      $cert_expiry_date = cert_expiry_date($raw_cert);
      $cert_validfrom_date = cert_valid_from($raw_cert);

      $now = time();
      $datefromdiff = $now - $cert_validfrom_date;
      $datetodiff = $now - $cert_expiry_date;
      $cert_valid_days_ago = floor($datefromdiff/(60*60*24));
      $cert_valid_days_ahead = floor($datetodiff/(60*60*24));

      $unsublink = "https://" . $current_link . "/unsubscribe.php?id=" . $id;

      $to      = $email;
      $subject = "网站 " . htmlspecialchars($domain) . " 的证书已于 " . htmlspecialchars($days) . " 天前过期";
      $message = "您好，\r\n\r\n您之前申请了域名 " . htmlspecialchars($domain) . " 的网站证书过期提醒服务。\r\n\r\n我们发现，下列域名证书链中的某一证书已于 " . htmlspecialchars($days) . " 前过期:\r\n\r\n域名: " . htmlspecialchars($domain) . "\r\n证书通用名: " . htmlspecialchars($cert_cn) . "\r\n证书标题: " . htmlspecialchars($cert_subject) . "\r\n证书序列号: " . htmlspecialchars($cert_serial) . "\r\n证书有效期始于: " . htmlspecialchars(date("Y-m-d  H:i:s T", $cert_validfrom_date)) . " (" . $cert_valid_days_ago . " 天前)\r\n证书有效期止于: " . htmlspecialchars(date("Y-m-d  H:i:s T", $cert_expiry_date)) . " (" . $cert_valid_days_ahead . " 天前)\r\n\r\n请尽快续费或更换您的证书。\r\n\r该网站目前处于错误状态，所有访客皆能看到您网站的证书错误。请尽快更换证书解决该问题。\r\n\r\n如果您不想再接收关于该网站的邮件提醒，请点击下面的链接取消订阅:\r\n\r\n" . $unsublink . "\r\n\r\n\r\n 祝您健康愉快,\r\n网站证书过期检测提醒 by 香菇肥牛\r\nhttps://" . $current_link . "";
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

    } 
  }
  
}

function send_expires_in_email($days, $domain, $email, $raw_cert) {
  global $current_domain;
  global $current_link;
  global $check_file;
  $domain = trim($domain);
  echo "\t\t网站 " . $domain . " 的证书将在 " . $days . " 天后过期。\n";

  $file = file_get_contents($check_file);
  if ($file === FALSE) {
      echo "\t\t无法打开数据库。\n";
      return false;
  }
  $json_a = json_decode($file, true);
  if ($json_a === null && json_last_error() !== JSON_ERROR_NONE) {
      echo "\t\t无法读取数据库。\n";
      return false;
  }

  foreach ($json_a as $key => $value) {

    if ($value["domain"] == $domain && $value["email"] == $email) {

      $id = $key;
      $cert_cn = cert_cn($raw_cert);
      $cert_subject = cert_subject($raw_cert);
      $cert_serial = cert_serial($raw_cert);
      $cert_expiry_date = cert_expiry_date($raw_cert);
      $cert_validfrom_date = cert_valid_from($raw_cert);

      $now = time();
      $datefromdiff = $now - $cert_validfrom_date;
      $datetodiff = $cert_expiry_date - $now;
      $cert_valid_days_ago = floor($datefromdiff/(60*60*24));
      $cert_valid_days_ahead = floor($datetodiff/(60*60*24));

      $unsublink = "https://" . $current_link . "/unsubscribe.php?id=" . $id;

      $to      = $email;
      $subject = "网站 " . htmlspecialchars($domain) . " 的证书将于 " . htmlspecialchars($days) . " 天后过期";
      $message = "您好，\r\n\r\n您之前申请了域名 " . htmlspecialchars($domain) . " 的网站证书过期检测服务。\r\n\r\n我们发现下列域名的证书链中的某一证书将于 " . htmlspecialchars($days) . " 天后过期:\r\n\r\n域名: " . htmlspecialchars($domain) . "\r\n证书通用名: " . htmlspecialchars($cert_cn) . "\r\n证书标题: " . htmlspecialchars($cert_subject) . "\r\n证书序列号: " . htmlspecialchars($cert_serial) . "\r\n证书有效期始于: " . htmlspecialchars(date("Y-m-d  H:i:s T", $cert_validfrom_date)) . " (" . $cert_valid_days_ago . " 天前)\r\n证书有效期止于: " . htmlspecialchars(date("Y-m-d  H:i:s T", $cert_expiry_date)) . " (剩余 " . $cert_valid_days_ahead . " 天)\r\n\r\n请您在证书到期前续费或更换证书。\r\n\r\n若证书到期前仍未续费或更换，您的网站将发生证书错误，并将使所有访客知悉。\r\n\r\n若您不想再接受关于该域名的网站证书过期提醒邮件，请点击下面的链接取消订阅:\r\n\r\n" . $unsublink . "\r\n\r\n\r\n 祝您健康愉快,\r\n网站证书过期检测 by 香菇肥牛\r\nhttps://" . $current_link . "";
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

    } 
  }
}


?>
