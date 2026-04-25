FROM php:8.2-fpm

# Install dependencies for WordPress
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libicu-dev \
    zip \
    unzip \
    nano \
    curl \
    --no-install-recommends \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    gd \
    mysqli \
    pdo_mysql \
    zip \
    exif \
    mbstring \
    intl \
    opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy source code and import files directly to the working directory
COPY --chown=www-data:www-data ./source/ .
COPY --chown=www-data:www-data ./docker/import/ .

# Set permissions for the files
RUN find . -type d -exec chmod 755 {} \; && \
    find . -type f -exec chmod 644 {} \;

# No need for custom entrypoint anymore as code is already in place
CMD ["php-fpm"]