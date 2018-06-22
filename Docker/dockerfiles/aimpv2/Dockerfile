#FROM php:apache
FROM laravel-app-pre
COPY src/ /var/www/html/
COPY .env /var/www/html/.env

COPY apache2.conf /etc/apache2/apache2.conf
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN apt-get update \
#  && apt-get install -y git zlib1g-dev \
#  && apt-get install -y git \
#  && docker-php-ext-install zip \
#  && docker-php-ext-install pdo \
#  && docker-php-ext-install pdo_mysql \
  && ln -s /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/rewrite.load \
  && mkdir storage \
  && mkdir storage/framework \
  && mkdir storage/framework/cache \
  && mkdir storage/framework/sessions \
  && mkdir storage/framework/testing \
  && mkdir storage/framework/views \
  && chown -R www-data.www-data * \
  && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
  && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
  && curl -o /tmp/composer-setup.php https://getcomposer.org/installer \
  && curl -o /tmp/composer-setup.sig https://composer.github.io/installer.sig \
  && php -r "if (hash('SHA384', file_get_contents('/tmp/composer-setup.php')) !== trim(file_get_contents('/tmp/composer-setup.sig'))) { unlink('/tmp/composer-setup.php'); echo 'Invalid installer' . PHP_EOL; exit(1); }" \
  && php -f /tmp/composer-setup.php \
  && php composer.phar config repo.packagist composer https://packagist.phpcomposer.com \
  && php composer.phar install -vvv

COPY empty-batch-actions.blade.php /var/www/html/vendor/encore/laravel-admin/resources/views/grid/empty-batch-actions.blade.php
COPY BatchActions.php /var/www/html/vendor/encore/laravel-admin/src/Grid/Tools/BatchActions.php
COPY Field.php /var/www/html/vendor/encore/laravel-admin/src/Form/Field.php

