#!/usr/bin/env bash
set -x

# Login to aws
sudo $(aws ecr get-login --no-include-email --region eu-central-1)

export CONTAINERS=('php' 'apache')

export TAGNAME="$BRANCHNAME"

for CONTAINER in "${CONTAINERS[@]}"; do
  sudo docker tag $CONTAINER:$TAGNAME $ECR/&&SERVICE_NORMALIZED_NAME&&/$CONTAINER:$TAGNAME
  sudo docker push $ECR/&&SERVICE_NORMALIZED_NAME&&/$CONTAINER:$TAGNAME
done

if [[ $BRANCHNAME = "master" ]]
then
  export TAGNAME="$PLANKEY-$BUILDNUMBER"
  for CONTAINER in "${CONTAINERS[@]}"; do
      sudo docker tag $CONTAINER:$BRANCHNAME $ECR/&&SERVICE_NORMALIZED_NAME&&/$CONTAINER:$TAGNAME
      sudo docker push $ECR/&&SERVICE_NORMALIZED_NAME&&/$CONTAINER:$TAGNAME
  done
fi

