ARG VARIANT="0-8"
FROM mcr.microsoft.com/devcontainers/php:${VARIANT}

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions
RUN install-php-extensions pdo_mysql