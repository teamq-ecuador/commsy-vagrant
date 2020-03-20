#!/usr/bin/env bash

set -x

rm docker-compose.override.yaml

sudo -E BRANCH=$BRANCHNAME docker-compose build
