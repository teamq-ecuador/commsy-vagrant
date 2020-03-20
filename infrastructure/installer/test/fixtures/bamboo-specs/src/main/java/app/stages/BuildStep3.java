package app.stages;

import com.atlassian.bamboo.specs.api.builders.credentials.SharedCredentialsIdentifier;
import com.atlassian.bamboo.specs.api.builders.plan.artifact.ArtifactSubscription;
import com.atlassian.bamboo.specs.api.builders.task.Task;
import com.atlassian.bamboo.specs.builders.task.*;
import com.atlassian.bamboo.specs.model.task.ScriptTaskProperties;

public class BuildStep3 extends AbstractBuildStep {
    public BuildStep3() {
        super("BuildStep3", "Deploy to AWS", "DTA");
    }

    @Override
    protected ArtifactSubscription[] getArtifactSubscriptions() {
        return new ArtifactSubscription[] {
                new ArtifactSubscription()
                        .artifact("infrastructure.tar.gz"),
                new ArtifactSubscription()
                        .artifact("sourcecode.tar.gz")
        };
    }

    protected Task<?, ?>[] getTasks() {
        return new Task<?, ?>[] {
                new ArtifactDownloaderTask()
                        .description("Get artifacts")
                        .artifacts(new DownloadItem()
                        .artifact("infrastructure.tar.gz")),
                new SshTask().authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("default-lightsail"))
                        .description("Setup folder structure")
                        .host("${bamboo.AWS_TOOLS_TESTING_MASTER_HOST}")
                        .username("${bamboo.AWS_TESTING_MASTER_USERNAME}")
                        .command("if [ ! -d \"${bamboo.AWS_DEPLOY_ROOT_PATH}\" ] \r\nthen\r\n    sudo mkdir -p ${bamboo.AWS_DEPLOY_ROOT_PATH}\r\n    sudo chown ${bamboo.AWS_TESTING_MASTER_USERNAME}:${bamboo.AWS_TESTING_MASTER_USERNAME} ${bamboo.AWS_DEPLOY_ROOT_PATH}\r\nfi\r\n\r\nif [ ! -d \"${bamboo.AWS_DEPLOY_ROOT_PATH}/${bamboo.planRepository.branchName}\" ] \r\nthen\r\n    sudo mkdir \"${bamboo.AWS_DEPLOY_ROOT_PATH}/${bamboo.planRepository.branchName}\"\r\n    sudo chown ${bamboo.AWS_TESTING_MASTER_USERNAME}:${bamboo.AWS_TESTING_MASTER_USERNAME} \"${bamboo.AWS_DEPLOY_ROOT_PATH}/${bamboo.planRepository.branchName}\"\r\nfi"),
                new ScpTask()
                        .description("Copy docker files to AWS - Infrastructure")
                        .host("${bamboo.AWS_TOOLS_TESTING_MASTER_HOST}")
                        .username("${bamboo.AWS_TESTING_MASTER_USERNAME}")
                        .toRemotePath("${bamboo.AWS_DEPLOY_ROOT_PATH}/${bamboo.planRepository.branchName}")
                        .authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("default-lightsail"))
                        .fromArtifact(new ArtifactItem()
                        .artifact("infrastructure.tar.gz")),
                new SshTask().authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("default-lightsail"))
                        .description("Unpack files")
                        .host("${bamboo.AWS_TOOLS_TESTING_MASTER_HOST}")
                        .username("${bamboo.AWS_TESTING_MASTER_USERNAME}")
                        .command("sudo tar -xzvf \"${bamboo.AWS_DEPLOY_ROOT_PATH}/${bamboo.planRepository.branchName}/infrastructure.tar.gz\" -C \"${bamboo.AWS_DEPLOY_ROOT_PATH}/${bamboo.planRepository.branchName}\""),
                new SshTask().authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("default-lightsail"))
                        .description("Pull images")
                        .host("${bamboo.AWS_TOOLS_TESTING_MASTER_HOST}")
                        .username("${bamboo.AWS_TESTING_MASTER_USERNAME}")
                        .command("export ECR=${bamboo.AWS_ECR}\r\nexport BRANCHNAME=${bamboo.planRepository.branchName}\r\n\r\nsudo -E bash ${bamboo.AWS_DEPLOY_ROOT_PATH}/${bamboo.planRepository.branchName}/infrastructure/bamboo/build/deploy-to-aws/pull-images.sh"),
                new SshTask().authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("default-lightsail"))
                        .description("Run migrations")
                        .host("${bamboo.AWS_TOOLS_TESTING_MASTER_HOST}")
                        .username("${bamboo.AWS_TESTING_MASTER_USERNAME}")
                        .command("set -x\r\n\r\nBRANCH=$(echo \"${bamboo.planRepository.branchName}\" | tr '[:upper:]' '[:lower:]'); export BRANCH\r\n\r\nsudo docker run --rm -e APP_ENV=testing ${bamboo.AWS_ECR}/##Service_Name##/php:$BRANCH php /var/www/app/bin/console doctrine:migrations:migrate"),
                new SshTask().authenticateWithSshSharedCredentials(new SharedCredentialsIdentifier("default-lightsail"))
                        .description("Deploy to Stack")
                        .host("${bamboo.AWS_TOOLS_TESTING_MASTER_HOST}")
                        .username("${bamboo.AWS_TESTING_MASTER_USERNAME}")
                        .command("set -x\r\n\r\nexport DOCKERPATH=\"${bamboo.AWS_DEPLOY_ROOT_PATH}/${bamboo.planRepository.branchName}\"\r\nexport BRANCHNAME=\"${bamboo.planRepository.branchName}\"\r\n\r\nsudo -E bash ${bamboo.AWS_DEPLOY_ROOT_PATH}/${bamboo.planRepository.branchName}/infrastructure/bamboo/build/deploy-to-aws/deploy-config-to-stack.sh")
        };
    }
}
