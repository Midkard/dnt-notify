#!/usr/bin/env bash

set -eu

PROJECT_NAME=$(basename $(pwd))

if [ -d "$PROJECT_NAME" ]; then
    rm -rf "$PROJECT_NAME"
fi
mkdir "$PROJECT_NAME"

docker build --no-cache -t temporary_build -f environment/local/docker/php.Dockerfile .
docker create --name temporary_build_c temporary_build
docker cp temporary_build_c:/workdir/dist/. "./$PROJECT_NAME"
docker rm -f temporary_build_c
docker rmi temporary_build

docker buildx prune --all -f

zip -r "$PROJECT_NAME".zip "$PROJECT_NAME"/

rm -r "$PROJECT_NAME"/




