FROM dunglas/frankenphp:1-php8.4-alpine

WORKDIR /app

RUN apk add --no-cache ca-certificates \
    && install-php-extensions pdo_mysql

COPY Caddyfile /etc/caddy/Caddyfile
COPY . /app

ENV PORT=80

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
