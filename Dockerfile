FROM php:8.2-apache

RUN a2enmod rewrite

WORKDIR /var/www/html
COPY . /var/www/html

RUN if [ -f .env.render ]; then cp -f .env.render .env; fi \
    && mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/logs bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache database \
    && sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 10000

CMD ["sh", "-c", "sed -i \"s/80/${PORT:-10000}/g\" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf && php artisan config:clear && php artisan view:clear && php artisan migrate --force || true && apache2-foreground"]
