FROM dunglas/frankenphp:1.7-builder-php8.2

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /app
COPY . .

# 安装编译扩展所需依赖
RUN apt-get update && apt-get install -y \
    git unzip curl wget gnupg lsb-release \
    libzip-dev zip libssl-dev

# 编译并启用 PHP 扩展（路径正确，能被 /usr/local PHP 加载）
RUN docker-php-ext-install sockets zip bcmath
RUN docker-php-ext-install pdo pdo_mysql

# 安装 Composer 并优化依赖
RUN curl -sS https://getcomposer.org/installer | php && \
    php composer.phar update --no-dev --optimize-autoloader

# 设置文档根目录（用于 Laravel 或其他框架）
ENV DOCUMENT_ROOT=/app/public
