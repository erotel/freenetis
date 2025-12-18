# FreenetIS

FreenetIS is an open source information system for managing community-based networks.
It provides features like payment management, user and device management, services management etc.

* [Home page](http://www.freenetis.org)
* [Documentation (CZE)](http://wiki.freenetis.org)
* [Installation instruction (CZE)](http://wiki.freenetis.org/index.php/Instalace)
* [Developer page](https://dev.freenetis.org/projects/freenetis)
* [DEB repository](http://repository.freenetis.org)


úpravy pro Pvfree.net

pokud používáte výstup přihlášky do pdf, je nutno nainstalovat mpdf


apt install composer

cd /usr/share/freenetis/application/vendors/

composer require mpdf/mpdf

cd /var/www/html/freenetis

composer config platform.php 7.4.33

rm -rf vendor

composer require mpdf/mpdf:"8.1.6"


mkdir -p /var/www/html/freenetis/application/vendors/vendor/mpdf/mpdf/tmp/mpdf

chown -R www-data:www-data /var/www/html/freenetis/application/vendors/vendor/mpdf/mpdf/tmp

chmod -R 770 /var/www/html/freenetis/application/vendors/vendor/mpdf/mpdf/tmp
