FROM php:8.2-apache

# Mods de Apache que necesitamos
RUN a2enmod rewrite headers

# DocumentRoot => public y permitir .htaccess
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/000-default.conf \
 && printf "<Directory ${APACHE_DOCUMENT_ROOT}>\n\tAllowOverride All\n\tRequire all granted\n</Directory>\n" \
    > /etc/apache2/conf-available/override.conf \
 && a2enconf override

WORKDIR /var/www/html
COPY . .

# Entrypoint para que Apache escuche en $PORT (Render/Railway/Fly)
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
CMD ["/usr/local/bin/entrypoint.sh"]
