#!/usr/bin/env bash

set -x

BRANCH=$(echo "$BRANCHNAME" | tr '[:upper:]' '[:lower:]'); export BRANCH
SUBNETWORK=$((1 + RANDOM % 10)); export SUBNETWORK
STACKNAME=sso-$BRANCH; export STACKNAME
TESTING_HOST="$BRANCH.&&SERVICE_NORMALIZED_NAME&&.tools.testing.giffits.de"; export TESTING_HOST

# Login to AWS
sudo $(aws ecr get-login --no-include-email --region eu-central-1)

cd $DOCKERPATH

function replacePlaceholderInConfig() {
    local PLACEHOLDER=$1
    local KEY=$2
    sudo sed -i "s/$PLACEHOLDER/$KEY/g" stack.testing.yaml
}

replacePlaceholderInConfig "#random#" $SUBNETWORK
replacePlaceholderInConfig "##branch##" $BRANCHNAME
replacePlaceholderInConfig "##testing_host##" $TESTING_HOST

docker stack rm "$STACKNAME"

export LIMIT=40
until [ -z "$(docker ps --filter label=com.docker.stack.namespace=$STACKNAME -q)" ] || [ "$LIMIT" -lt 0 ]; do
  export LIMIT=$LIMIT-1
  sleep 1;
done

export LIMIT=40
until [ -z "$(docker network ls --filter label=com.docker.stack.namespace=$STACKNAME -q)" ] || [ "$LIMIT" -lt 0 ]; do
  export LIMIT=$LIMIT-1
  sleep 1;
done

docker stack deploy --compose-file=stack.testing.yaml $STACKNAME --with-registry-auth
