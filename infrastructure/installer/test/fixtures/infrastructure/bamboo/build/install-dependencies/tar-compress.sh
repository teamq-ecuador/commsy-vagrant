#!/usr/bin/env bash

set -x

tar --exclude="./infrastructure" --exclude='./infrastructure' -czvf ./sourcecode.tar.gz app
tar -czvf ./infrastructure.tar.gz infrastructure docker-compose.yaml docker-compose.override.yaml stack.testing.yaml
