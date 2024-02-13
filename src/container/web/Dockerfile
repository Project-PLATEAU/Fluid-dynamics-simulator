FROM ubuntu:22.04


# Configuring tzdata settings
ENV TZ=Asia/Tokyo
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# vim, git, nodejs, npm, php インストール
RUN apt-get update -y && \
  apt-get install -y vim git sudo \
  nodejs \
  npm -y \
  php-cli \
  libapache2-mod-php8.1 \
  php8.1-pgsql \
  php8.1-mbstring php8.1-bcmath php-json php8.1-xml \
  php8.1-zip php8.1-pdo php8.1-common php8.1-tokenizer \
  php8.1-curl php8.1-gd php8.1-soap php8.1-xmlrpc

# apache2 インストール
RUN apt-get update -y && apt-get install -y apache2

# install xdebug
# https://xdebug.org/download/historical
RUN apt-get update -y && apt-get install php8.1-xdebug

WORKDIR /var/www/html

# install composer
# https://hub.docker.com/_/composer/tags?page=1
COPY --from=composer:2.6.4 /usr/bin/composer /usr/bin/composer

# update httpd.conf
COPY ./sites-available/000-default.conf /etc/apache2/sites-available/000-default.conf

# update php.ini
COPY ./php.ini /etc/php/8.1/apache2/php.ini

# update apache2.conf
COPY ./apache2.conf /etc/apache2/apache2.conf

# a2emod rewrite をして apache に rewrite モジュールを追加
# これをしないと Laravel でルート以外にアクセスできない
RUN a2enmod rewrite && \
  service apache2 restart

EXPOSE 80
CMD ["apachectl", "-D", "FOREGROUND"]
