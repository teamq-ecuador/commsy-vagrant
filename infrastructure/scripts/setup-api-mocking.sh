#!/usr/bin/env bash
docker rm -v -f "/mockserver" || true
docker run -d -P --name=mockserver --restart=always -p 1080:1080 jamesdbloom/mockserver