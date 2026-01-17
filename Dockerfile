FROM php:8.4-cli

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    libbrotli-dev \
    libssl-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd sockets

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Swoole extension
RUN pecl install swoole && docker-php-ext-enable swoole

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create system user to run Composer and Artisan Commands
RUN groupadd -g 1000 dev && \
    useradd -u 1000 -g dev -G root -m -d /home/dev -s /bin/bash dev
RUN mkdir -p /home/dev/.composer && \
    chown -R dev:dev /home/dev

# Set permissions
RUN chown -R dev:dev /var/www

USER dev

EXPOSE 8000

# Install dependencies if not present (only if you want it automated, but usually done manually)
# RUN composer install --no-interaction --optimize-autoloader

CMD ["php", "artisan", "octane:start", "--server=swoole", "--host=0.0.0.0", "--port=8000"]
