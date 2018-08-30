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


if ( isset($_GET['id']) && !empty($_GET['id'])  ) {
  $id = htmlspecialchars($_GET['id']);
  $userip = $_SERVER["HTTP_X_FORWARDED_FOR"] ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
  if ( isset($_GET['cron']) && !empty($_GET['cron'])  ) {
    $cron = htmlspecialchars($_GET['cron']);
  } 
  if ($cron = "auto") {
    $userip = "错误；已自动取消订阅。";
  }
  $uuid_pattern = "/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/";
  if (preg_match($uuid_pattern, $id)) {
    $remove_domain = remove_domain_check($id, $userip);
    if (is_array($remove_domain["errors"]) && count($remove_domain["errors"]) != 0) {
      $errors = array_unique($remove_domain["errors"]);
      foreach ($remove_domain["errors"] as $key => $err_value) {
        echo "<div class='alert alert-danger' role='alert'>";
        echo htmlspecialchars($err_value);
        echo "</div>";
      }
    } else {
      echo "<div class='alert alert-success' role='alert'>";
      echo "已取消检测；您将不会收到关于该域名的证书到期的邮件提醒。<br>";
      echo "</div>";
    }
  } else {
      echo "<div class='alert alert-danger' role='alert'>";;
      echo "错误。验证码无效。<br>";
      echo "请返回重试。<br>";
      echo "</div>";
  }
} else {
  echo "<div class='alert alert-danger' role='alert'>";;
  echo "错误。需要验证码。<br>";
  echo "请返回重试。<br>";
  echo "</div>";
}

echo "<div class='content'><section id='faq'>";
require('inc/faq.php');
echo "</div>";

require('inc/footer.php');

?>
