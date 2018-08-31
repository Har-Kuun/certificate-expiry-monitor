## About the fork:

This is the Chinese-simplified version of the original Certficate Expiry Monitor.  Example site in Chinese Simplified: https://ssl.qing.su/

这是原版本Certificate Expiry Monitor的中文翻译版本。示范站点：https://ssl.qing.su/

Version 1.3 was forked and translated.  The original php mail() was replaced with Pear mail function.  If you would like to restore the old mail() function, please change the related .php files in /functions/ directory accordingly.

我们fork了1.3版本，并进行了翻译。为了更好的中文处理，我们剔除了原版本的mail()函数，而用PEAR的mail包来代替。

# Certificate Expiry Monitor 网站证书过期检测

## About 关于本项目

Certificate Expiry Monitor is an open source monitoring tool for certificates. It monitors websites and emails you when the certificates are about to expire.

网站证书过期检测是一个开源工具，它可以检测网站的证书，并在到期之前发邮件提醒您何时到期。

See the example site in English: https://certificatemonitor.org/

英文版示范站点：https://certificatemonitor.org/

## Requirements 系统要求

- PHP 5.6+
- OpenSSL
- PHP must allow remote fopen.

## Installation  安装步骤

Unpack, change some variables, setup a cronjob and go!

First get the code and unpack it to your webroot:

首先，我们获取源码，然后解压至网站目录

    cd /srv/www/ssl.qing.su/
    git clone https://github.com/Har-Kuun/certificate-expiry-monitor_zh-CN.git
    mv certificate-expiry-monitor public_html

Create the database files, outside of your webroot. If you create these inside your webroot, everybody can read them.

新建数据库文件（注意将文件放在网站目录之外）

    touch /srv/www/ssl.qing.su/db/pre_checks.json
    touch /srv/www/ssl.qing.su/db/checks.json
    touch /srv/www/ssl.qing.su/db/deleted_checks.json
    chown -R www-data /srv/www/ssl.qing.su/db/*.json

These files are used by the tool as database for checks.


Change the location of these files in `variables.php`:

在`/functions/variables.php`文件中更改对应的数据库文件地址

    // set this to a location outside of your webroot so that it cannot be accessed via the internets.

    $pre_check_file = '/srv/www/ssl.qing.su/db/pre_checks.json';
    $check_file = '/srv/www/ssl.qing.su/db/checks.json';
    $deleted_check_file = '/srv/www/ssl.qing.su/db/deleted_checks.json';

Also change the `$current_domain` variable, it is used in all the email addresses.

更改`$current_domain`参数为您网站的地址

    $current_domain = "ssl.qing.su";

And `$current_link`, which may or may not be the same. It is used in the confirm and unsubscribe links, and depends on your webserver configuration. `example.com/subdir` here means your unsubscribe links will start `https://example.com/subdir/unsubscribe.php`.

更改`$current_domain`参数为您网站的地址

    $current_link = "ssl.qing.su";

Set up the cronjob to run once a day:

设置cronjob

    # /etc/cron.d/certificate-exipry-monitor
    1 1 * * * www-data /path/to/php /srv/www/ssl.qing.su/public_html/cron.php >> /var/log/certificate-expiry-monitor.log 2>&1


The default timeout for checks is 2 seconds. If this is too fast for your internal services, this can be raised in the `variables.php` file.

证书检测的超时时限是两秒，您可以在`variables.php`文件中更改这个参数。


Finally, install php-pear and the Mail package in order to use the SMTP mail function.

最后，安装php-pear和对应的Mail包。
    
    apt-get install php-pear
    pear install Mail
    pear install Net_SMTP


Enjoy!  

中文版帮助与支持请访问 https://qing.su/article/sslcheck.html

