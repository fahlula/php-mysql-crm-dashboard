FROM ubuntu:22.04

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV DB_HOST=127.0.0.1 \
    DB_NAME=crm_database \
    DB_USER=crm_user \
    DB_PASS=crm_password \
    PORT=80

RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    libapache2-mod-php8.1 \
    php8.1-mysql \
    php8.1-gd \
    php8.1-zip \
    php8.1-mbstring \
    php8.1-xml \
    php8.1-curl \
    mysql-server \
    supervisor \
    curl \
    unzip \
    && a2enmod rewrite \
    && a2enmod php8.1 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY backend/public/ /var/www/html/
COPY migrations/ /migrations/

RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html\n\
    <Directory /var/www/html>\n\
        AllowOverride All\n\
        Require all granted\n\
        DirectoryIndex index.php index.html\n\
    </Directory>\n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

RUN a2ensite 000-default \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && rm -f /var/www/html/index.html

# Set default charset for PHP and Apache to UTF-8
RUN echo "default_charset = UTF-8" > /etc/php/8.1/apache2/conf.d/zz-charset.ini \
    && echo "AddDefaultCharset UTF-8" > /etc/apache2/conf-available/charset.conf \
    && a2enconf charset

RUN echo '[supervisord]\n\
nodaemon=true\n\
\n\
[program:mysql]\n\
command=/usr/bin/mysqld_safe --user=mysql\n\
autostart=true\n\
autorestart=true\n\
stdout_logfile=/var/log/mysql.log\n\
stderr_logfile=/var/log/mysql.log\n\
\n\
[program:apache2]\n\
command=/usr/sbin/apache2ctl -D FOREGROUND\n\
autostart=true\n\
autorestart=true\n\
stdout_logfile=/var/log/apache2.log\n\
stderr_logfile=/var/log/apache2.log' > /etc/supervisor/conf.d/supervisord.conf

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]