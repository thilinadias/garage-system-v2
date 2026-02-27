FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    cron \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd zip mysqli

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Set permissions
RUN mkdir -p /var/www/html/assets/uploads/profiles \
    && chown -R www-data:www-data /var/www/html/assets/uploads \
    && chmod -R 777 /var/www/html/assets/uploads

# Setup Cron Job for Automated Reports
RUN echo "0 * * * * root /usr/local/bin/php /var/www/html/includes/cron_reports.php >> /var/log/cron.log 2>&1" > /etc/cron.d/garage-cron \
    && chmod 0644 /etc/cron.d/garage-cron \
    && crontab /etc/cron.d/garage-cron \
    && touch /var/log/cron.log

# Expose port 80
EXPOSE 80

# Start Cron daemon and Apache
CMD cron && apache2-foreground
