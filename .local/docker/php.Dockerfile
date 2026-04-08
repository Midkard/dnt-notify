FROM registry.dn-ms.ru/dnt-theme/php/8.2:1.0.0

RUN mkdir /workdir
RUN mkdir /workdir/wp
COPY . /workdir/wp
COPY .local/copy.php /workdir/
RUN cd /workdir/wp; composer install --prefer-dist --optimize-autoloader --no-ansi --no-interaction --no-dev
RUN cd /workdir; php copy.php
