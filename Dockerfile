FROM php:7.4-apache

# Install packages
RUN export DEBIAN_FRONTEND=noninteractive && \
    apt-get update && \
    apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        default-mysql-client \
        nullmailer git cron \
        libapache2-mod-evasive \
    && \
    apt-get -y autoremove && \
    apt-get -y clean && \
    rm -rf /var/lib/apt/lists/*

# Apache modules
RUN a2enmod headers && \
    a2enmod evasive && \
    a2enmod reqtimeout && \
    a2enmod rewrite

# Apache config
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf && \
    sed -i 's/Options Indexes.*/Options FollowSymLinks/g' /etc/apache2/apache2.conf && \
    mkdir -p /var/log/mod_evasive && \
    chown -R www-data:www-data /var/log/mod_evasive && \
    touch /var/log/wtmp

COPY config/apache_evasive.conf /etc/apache2/mods-available/evasive.conf
COPY config/apache_security.conf /etc/apache2/conf-enabled/security.conf
COPY config/apache_mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

# PHP extensions
RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) pdo_mysql gd

# PHP libaries
RUN git clone https://github.com/tecnickcom/TCPDF.git /usr/local/lib/php/tcpdf

# PHP config
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
COPY config/php.ini /usr/local/etc/php/conf.d/scoreboard.ini

# Setup CRON
COPY config/crontab /tmp/crontab
RUN crontab /tmp/crontab && \
    rm -f /tmp/crontab

COPY html/ /var/www/html/
