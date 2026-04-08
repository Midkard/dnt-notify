FROM ghcr.io/midkard/wp-php/8.2:1.0.0

ARG USER_ID=1000

RUN mkdir /config
COPY ./config /config/
RUN cd /config; install-config


RUN groupadd --gid $USER_ID vscode \
    && useradd -s /bin/bash --uid $USER_ID --gid $USER_ID -m vscode \
    && usermod -a -G vscode www-data \
    && usermod -a -G www-data vscode \
    && chmod -R 777 /var/www/html

RUN mv /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini \
    && echo "upload_max_filesize = 500M" >> /usr/local/etc/php/php.ini \
    && echo "post_max_size = 500M" >> /usr/local/etc/php/php.ini

USER vscode

# RUN curl -fsSL https://fnm.vercel.app/install | bash -s -- --install-dir "/home/vscode/.fnm"
# ENV PATH="/home/vscode/.fnm:$PATH"
# RUN eval "$(fnm env --shell bash)"

# Download and install Node.js:
# RUN fnm install 24

ENTRYPOINT [ "apache2-foreground" ]