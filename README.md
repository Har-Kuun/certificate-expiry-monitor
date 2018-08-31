# Certificate Expiry Monitor

This is the Chinese-simplified version of the original Certficate Expiry Monitor.  Example site in Chinese Simplified: https://ssl.qing.su/

Version 1.3 was forked and translated.  Other codes were kept untouched.

## About

Certificate Expiry Monitor is an open source monitoring tool for certificates. It monitors websites and emails you when the certificates are about to expire.

See the example site in English: https://certificatemonitor.org/

## Requirements

- PHP 5.6+
- OpenSSL
- PHP must allow remote fopen.

## Installation

Unpack, change some variables, setup a cronjob and go!

First get the code and unpack it to your webroot:

    cd /srv/www/ssl.qing.su/
    git clone https://github.com/Har-Kuun/certificate-expiry-monitor_zh-CN.git

Create the database files, outside of your webroot. If you create these inside your webroot, everybody can read them.

    touch /srv/www/ssl.qing.su/db/pre_checks.json
    touch /srv/www/ssl.qing.su/db/checks.json
    touch /srv/www/ssl.qing.su/db/deleted_checks.json
    chown -R www-data /var/www/certificate-expiry-monitor-db/*.json

These files are used by the tool as database for checks.


Change the location of these files in `variables.php`:


    // set this to a location outside of your webroot so that it cannot be accessed via the internets.

    $pre_check_file = '/srv/www/ssl.qing.su/db/pre_checks.json';
    $check_file = '/srv/www/ssl.qing.su/db/checks.json';
    $deleted_check_file = '/srv/www/ssl.qing.su/db/deleted_checks.json';

Also change the `$current_domain` variable, it is used in all the email addresses.

    $current_domain = "ssl.qing.su";

And `$current_link`, which may or may not be the same. It is used in the confirm and unsubscribe links, and depends on your webserver configuration. `example.com/subdir` here means your unsubscribe links will start `https://example.com/subdir/unsubscribe.php`.

    $current_link = "ssl.qing.su";

Set up the cronjob to run once a day:

    # /etc/cron.d/certificate-exipry-monitor
    1 1 * * * www-data /path/to/php /srv/www/ssl.qing.su/public_html/cron.php >> /var/log/certificate-expiry-monitor.log 2>&1


The default timeout for checks is 2 seconds. If this is too fast for your internal services, this can be raised in the `variables.php` file.
