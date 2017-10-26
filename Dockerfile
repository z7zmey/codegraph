FROM php:7.0

RUN mkdir /src
WORKDIR /src

RUN docker-php-ext-install sockets

COPY codegraph /codegraph/
COPY php-worker /codegraph/php-worker
COPY dist/app /codegraph/ui/dist/app

EXPOSE 8080
CMD ["/codegraph/codegraph", "-h", "0.0.0.0"]