FROM php:8.2.6-cli

WORKDIR /app

RUN apt-get update && apt-get install git -y

# Install workdir rules
RUN chown -R www-data:www-data /app

# Install composer
RUN curl -sS https://getcomposer.org/installer  \
    | php -- --install-dir=/usr/local/bin --filename=composer

# Container user & group
ARG USER_ID
ARG GROUP_ID

# Check if user and group doesn't exist before creating
RUN getent group "$GROUP_ID" || addgroup --gid "$GROUP_ID" user
RUN getent passwd "$USER_ID" || adduser --disabled-password --gecos '' --uid "$USER_ID" --gid "$GROUP_ID" user

EXPOSE 9000

USER "$USER_ID"
