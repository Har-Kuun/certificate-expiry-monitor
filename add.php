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
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

error_reporting(E_ALL & ~E_NOTICE);
foreach (glob("functions/*.php") as $filename) {
  require($filename);
}

require('inc/header.php');

echo "<div class='content'><section id='result'>";

if ( isset($_POST['email']) && !empty($_POST['email']) && isset($_POST['domains']) && !empty($_POST['domains']) ) {

  $errors = array();
  if (validate_email($_POST['email'])) {
    $email = htmlspecialchars($_POST['email']);
  } else {
    $errors[] = "邮箱地址无效。";
  }

  $domains = validate_domains($_POST['domains']);
  if ( count($domains['errors']) >= 1 ) {
    foreach ($domains['errors'] as $key => $value) {
      $errors[] = $value;
    }
  } 
  
  if (is_array($errors) && count($errors) != 0) {
    $errors = array_unique($errors);
    foreach ($errors as $key => $value) {
      echo "<div class='alert alert-danger' role='alert'>";
      echo htmlspecialchars($value);
      echo "</div>";
    }
    echo "请返回重试。<br>";
  } elseif ( is_array($errors) && count($errors) == 0 && is_array($domains['domains']) && count($domains['domains']) != 0 && count($domains['domains']) < 21) {
    echo "<div class='alert alert-info' role='alert'>";
    echo "邮箱: " . htmlspecialchars($email) . ".<br>";
    echo "</div>";
    foreach ($domains['domains'] as $key => $value) {
      $userip = $_SERVER["HTTP_X_FORWARDED_FOR"] ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
      $add_domain = add_domain_to_pre_check($value, $email, $userip);
      if (is_array($add_domain["errors"]) && count($add_domain["errors"]) != 0) {
        $errors = array_unique($add_domain["errors"]);
        foreach ($add_domain["errors"] as $key => $err_value) {
          echo "<div class='alert alert-danger' role='alert'>";
          echo htmlspecialchars($err_value);
          echo "</div>";
        }
      } else {
        echo "<div class='alert alert-success' role='alert'>";
        echo "已向您的邮箱发送确认邮件。请点击确认邮件中的链接以启用证书过期检测。<br>";
        echo "</div>";
      }
    }
  } else {
    echo "<div class='alert alert-danger' role='alert'>";
    echo "域名太多。<br>";
    echo "请返回重试。<br>";
    echo "</div>";
  }
} else {

  echo "<div class='alert alert-danger' role='alert'>";;
  echo "错误。请输入域名和邮箱。<br>";
  echo "请返回重试。<br>";
  echo "</div>";
}


require('inc/faq.php');

require('inc/footer.php');

?>
