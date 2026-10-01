FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && printf '<Directory /var/www/html/app>\n    Require all denied\n</Directory>\n' > /etc/apache2/conf-enabled/retoma.conf \
    && sed -i 's/ServerTokens OS/ServerTokens Prod/' /etc/apache2/conf-available/security.conf

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

RUN mv /var/www/html/iniciar.sh /usr/local/bin/iniciar.sh && chmod +x /usr/local/bin/iniciar.sh

CMD ["/usr/local/bin/iniciar.sh"]
