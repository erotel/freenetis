# FreenetIS

FreenetIS is an open source information system for managing community-based networks.
It provides features like payment management, user and device management, services management etc.

- [Home page](http://www.freenetis.org)
- [Documentation (CZE)](http://wiki.freenetis.org)
- [Installation instruction (CZE)](http://wiki.freenetis.org/index.php/Instalace)
- [Developer page](https://dev.freenetis.org/projects/freenetis)
- [DEB repository](http://repository.freenetis.org)

úpravy pro Pvfree.net

pokud používáte výstup přihlášky do pdf, je nutno nainstalovat mpdf

apt install composer

cd /usr/share/freenetis/application/vendors/

composer require mpdf/mpdf

cd /var/www/html/freenetis

composer config platform.php 7.4.33

rm -rf vendor

composer require mpdf/mpdf:"8.1.6"

mkdir -p /usr/share/freenetis/application/cache/mpdf

chown -R www-data:www-data /usr/share/freenetis/application/cache/mpdf

chmod 2775 /usr/share/freenetis/application/cache/mpdf


composer.json {
  "require": {
    "php": ">=7.4",
    "symfony/mailer": "^5.4",
    "symfony/mime": "^5.4"
  },
  "config": {
    "platform": {
      "php": "7.4.0"
    }
  }
}

rm -rf vendor composer.lock
composer install

composer require symfony/mailer symfony/mime

