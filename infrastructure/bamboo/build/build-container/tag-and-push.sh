#!/usr/bin/env bash
set -x

# Login to aws
sudo $(aws ecr get-login --no-include-email --region eu-central-1)

export CONTAINERS=('php' 'apache')

export TAGNAME="$BRANCHNAME"

for CONTAINER in "${CONTAINERS[@]}"; do
  sudo docker tag sso_$CONTAINER:$TAGNAME $ECR/sso/$CONTAINER:$TAGNAME
  sudo docker push $ECR/sso/$CONTAINER:$TAGNAME
done

if [[ $BRANCHNAME = "master" ]]
then
  export TAGNAME="$PLANKEY-$BUILDNUMBER"
  for CONTAINER in "${CONTAINERS[@]}"; do
      sudo docker tag sso_$CONTAINER:master $ECR/sso/$CONTAINER:master
      sudo docker push $ECR/sso/$CONTAINER:master
  done
fi

