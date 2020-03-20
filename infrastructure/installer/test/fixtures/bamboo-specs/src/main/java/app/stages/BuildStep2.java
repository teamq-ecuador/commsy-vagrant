package app.stages;

import com.atlassian.bamboo.specs.api.builders.plan.artifact.ArtifactSubscription;
import com.atlassian.bamboo.specs.api.builders.task.Task;
import com.atlassian.bamboo.specs.builders.task.*;
import com.atlassian.bamboo.specs.model.task.ScriptTaskProperties;

public class BuildStep2 extends AbstractBuildStep {
    public BuildStep2() {
        super("BuildStep2", "Build Containers", "BC");
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
                        .allArtifacts(true)),
                new ScriptTask()
                        .description("Unpack files")
                        .inlineBody("if [ ! -d \"${bamboo.build.working.directory}/unpacked\" ] \nthen\n    mkdir ${bamboo.build.working.directory}/unpacked\nfi\n\ntar -xzvf sourcecode.tar.gz -C ${bamboo.build.working.directory}/unpacked\ntar -xzvf infrastructure.tar.gz -C ${bamboo.build.working.directory}/unpacked"),
                new ScriptTask()
                        .description("Setup Integration Configs")
                        .location(ScriptTaskProperties.Location.FILE)
                        .fileFromPath("infrastructure/bamboo/build/build-container/setup-configs.sh")
                        .environmentVariables("BRANCHNAME=${bamboo.planRepository.branchName}")
                        .workingSubdirectory("unpacked"),
                new ScriptTask()
                        .description("Build containers")
                        .location(ScriptTaskProperties.Location.FILE)
                        .fileFromPath("infrastructure/bamboo/build/build-container/build-docker-container.sh")
                        .environmentVariables("BRANCHNAME=${bamboo.planRepository.branchName}")
                        .workingSubdirectory("unpacked"),
                new ScriptTask()
                        .description("Tag and push")
                        .location(ScriptTaskProperties.Location.FILE)
                        .fileFromPath("infrastructure/bamboo/build/build-container/tag-and-push.sh")
                        .environmentVariables("BRANCHNAME=${bamboo.planRepository.branchName} ECR=${bamboo.AWS_ECR} PLANKEY=${bamboo.planKey} BUILDNUMBER=${bamboo.buildNumber}")
                        .workingSubdirectory("unpacked")
        };
    }
}
