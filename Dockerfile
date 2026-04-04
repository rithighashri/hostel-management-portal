FROM php:8.2-apache

ENV PORT=10000
WORKDIR /var/www/html

# Copy app source
COPY . /var/www/html

# Configure Apache to listen on Render's expected port
RUN sed -ri "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf \
    && sed -ri "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf \
    && sed -ri "s/:80>/:${PORT}>/" /etc/apache2/sites-available/default-ssl.conf \
    && a2enmod rewrite

EXPOSE 10000

CMD ["apache2-foreground"]
