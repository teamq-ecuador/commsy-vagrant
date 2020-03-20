#!/usr/bin/env bash

set -x

BRANCH=$(echo "$BRANCHNAME" | tr '[:upper:]' '[:lower:]'); export BRANCH
export CONFIGPATH="$PWD/infrastructure/containers/apache/config/vhost"

sudo find $CONFIGPATH -type f -print0 | sudo xargs -0 sed -i "s/##testing_host##/$BRANCH.&&SERVICE_NORMALIZED_NAME&&.tools.testing.giffits.de/g"
sudo find $CONFIGPATH -type f -print0 | sudo xargs -0 sed -i "s/##testing_branch##/$BRANCH/g"
sudo find $CONFIGPATH -type f -print0 | sudo xargs -0 sed -i "s/##ENV##/testing/g"

sudo find "$PWD" -type f -print0 | sudo xargs -0 sed -i "s/##testing_host##/$BRANCH.&&SERVICE_NORMALIZED_NAME&&.tools.testing.giffits.de/g"
sudo find "$PWD" -type f -print0 | sudo xargs -0 sed -i "s/##testing_branch##/$BRANCH/g"
sudo find "$PWD" -type f -print0 | sudo xargs -0 sed -i "s/##ENV##/testing/g"
