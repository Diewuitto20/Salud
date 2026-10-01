FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* \
    && a2enmod mpm_prefork rewrite \
    && printf '<Directory /var/www/html/app>\n    Require all denied\n</Directory>\n' > /etc/apache2/conf-enabled/retoma.conf \
    && sed -i 's/ServerTokens OS/ServerTokens Prod/' /etc/apache2/conf-available/security.conf

COPY . /var/www/html/
RUN mv /var/www/html/iniciar.sh /usr/local/bin/iniciar.sh \
    && chmod +x /usr/local/bin/iniciar.sh \
    && chown -R www-data:www-data /var/www/html

CMD ["/usr/local/bin/iniciar.sh"]
