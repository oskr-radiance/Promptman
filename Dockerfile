# Use the official PHP 8.3 image with Apache
FROM php:8.3-apache

# 1. ポートを8080に変更
RUN sed -i 's/80/8080/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# 2. DocumentRoot はルート（/var/www/html）のまま
# ※ public/ を DocumentRoot にしない

# 3. mod_rewrite を有効化
RUN a2enmod rewrite

WORKDIR /var/www/html

# 4. ファイルをコピー
COPY . .

# 5. Apache設定を追加（アクセス制御）
RUN echo '<VirtualHost *:8080>\n\
    DocumentRoot /var/www/html\n\
    \n\
    # public/ と api/ のみHTTPアクセスを許可\n\
    <Directory /var/www/html>\n\
        Options -Indexes +FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    \n\
    # lib/, data/, storage/ へのHTTPアクセスを拒否\n\
    <Directory /var/www/html/lib>\n\
        Require all denied\n\
    </Directory>\n\
    \n\
    <Directory /var/www/html/data>\n\
        Require all denied\n\
    </Directory>\n\
    \n\
    <Directory /var/www/html/storage>\n\
        Require all denied\n\
    </Directory>\n\
    \n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# 6. 権限設定
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html && \
    chmod -R 777 /var/www/html/storage

EXPOSE 8080

CMD ["apache2-foreground"]
