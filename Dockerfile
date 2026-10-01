FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && printf '<Directory /var/www/html/app>\n    Require all denied\n</Directory>\n' > /etc/apache2/conf-enabled/retoma.conf \
    && sed -i 's/ServerTokens OS/ServerTokens Prod/' /etc/apache2/conf-available/security.conf

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

CMD sed -i "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf \
    && sed -i "s/:80>/:${PORT:-80}>/" /etc/apache2/sites-enabled/000-default.conf \
    && apache2-foreground
