FROM ubuntu:18.04

RUN apt update && \
  apt install -y \
  nginx \
  php-cli

ENTRYPOINT ["/usr/sbin/nginx", "-g", "daemon off;"]

EXPOSE 80