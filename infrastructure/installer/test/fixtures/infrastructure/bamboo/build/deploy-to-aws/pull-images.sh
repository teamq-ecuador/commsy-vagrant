#!/usr/bin/env bash

export CONTAINERS=("php" "apache")

for CONTAINER in "${CONTAINERS[@]}";
do
  echo "Pulling the image $CONTAINER:$BRANCHNAME"
  sudo docker pull $ECR/&&SERVICE_NORMALIZED_NAME&&/$CONTAINER:$BRANCHNAME
done


