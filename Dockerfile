FROM php:8.2-apache

RUN apt-get update && apt-get install -y libicu-dev libzip-dev unzip \
 && docker-php-ext-install intl zip \
 && a2enmod rewrite headers

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/000-default.conf \
 && printf "<Directory ${APACHE_DOCUMENT_ROOT}>\n\tAllowOverride All\n\tRequire all granted\n</Directory>\n" \
    > /etc/apache2/conf-available/override.conf \
 && a2enconf override

WORKDIR /var/www/html
COPY . .

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN if [ -f composer.json ]; then composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader; fi

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

CMD ["/usr/local/bin/entrypoint.sh"]
