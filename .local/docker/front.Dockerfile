FROM node:22

RUN mkdir /workdir
COPY . /workdir
RUN cd /workdir; npm install; npm run build
