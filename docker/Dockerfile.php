FROM php:8.3-fpm

RUN docker-php-ext-install pdo pdo_mysql mysqli

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-tdokei.ini

COPY docker/php-entrypoint.sh /usr/local/bin/tdokei-entrypoint.sh
RUN chmod +x /usr/local/bin/tdokei-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/tdokei-entrypoint.sh"]
CMD ["php-fpm"]
