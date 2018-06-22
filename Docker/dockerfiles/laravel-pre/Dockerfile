FROM php:apache

COPY ssh/* /root/.ssh/ 

RUN chmod -R 0600 /root/.ssh \
  && apt-get update \
  && apt-get install -y git zlib1g-dev vim mysql-client libfreetype6 libjpeg-dev libpng-dev \
  && docker-php-ext-install zip pdo_mysql gd 
#  && docker-php-ext-install zip \
#  && docker-php-ext-install pdo \
#  && docker-php-ext-install pdo_mysqli \
#  && docker-php-ext-install gd 
