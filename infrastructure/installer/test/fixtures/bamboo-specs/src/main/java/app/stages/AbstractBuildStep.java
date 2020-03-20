package app.stages;

import com.atlassian.bamboo.specs.api.builders.BambooKey;
import com.atlassian.bamboo.specs.api.builders.plan.Job;
import com.atlassian.bamboo.specs.api.builders.plan.Stage;
import com.atlassian.bamboo.specs.api.builders.plan.artifact.Artifact;
import com.atlassian.bamboo.specs.api.builders.plan.artifact.ArtifactSubscription;
import com.atlassian.bamboo.specs.api.builders.plan.configuration.AllOtherPluginsConfiguration;
import com.atlassian.bamboo.specs.api.builders.task.Task;
import com.atlassian.bamboo.specs.util.MapBuilder;

abstract public class AbstractBuildStep extends Stage{

    protected static boolean ARTIFACTS_DECLARED = false;

    public AbstractBuildStep(String name, String jobName, String bambooKey){
        super(name);

        Job job = new Job(jobName,
                new BambooKey(bambooKey))
                .pluginConfigurations(this.stageConfiguration())
                .tasks(this.getTasks())
                .artifactSubscriptions(this.getArtifactSubscriptions())
                .cleanWorkingDirectory(true);

        if(!ARTIFACTS_DECLARED){
            job.artifacts(this.getArtifacts());
            ARTIFACTS_DECLARED = true;
        }

        this.jobs(job);
    }

    protected ArtifactSubscription[] getArtifactSubscriptions() {
        return new ArtifactSubscription[] {};
    }

    abstract protected Task<?, ?>[] getTasks();

    protected Artifact[] getArtifacts() {

        Artifact sourceCode = new Artifact()
                .name("sourcecode.tar.gz")
                .copyPattern("sourcecode.tar.gz")
                .shared(true)
                .required(true);

        Artifact infrastructure = new Artifact()
                .name("infrastructure.tar.gz")
                .copyPattern("infrastructure.tar.gz")
                .shared(true)
                .required(true);

        return new Artifact[] {sourceCode, infrastructure};
    }

    protected AllOtherPluginsConfiguration stageConfiguration() {
        return new AllOtherPluginsConfiguration()
                .configuration(new MapBuilder()
                        .put("custom", new MapBuilder()
                                .put("browserstack", new MapBuilder()
                                        .put("BROWSERSTACK_LOCAL_ARGS", "")
                                        .put("app_build_path", "")
                                        .put("BROWSERSTACK_ACCESS_KEY", "")
                                        .put("BROWSERSTACK_USERNAME", "")
                                        .build())
                                .put("auto", new MapBuilder()
                                        .put("regex", "")
                                        .put("label", "")
                                        .build())
                                .put("buildHangingConfig.enabled", "false")
                                .put("ncover.path", "")
                                .put("clover", new MapBuilder()
                                        .put("path", "")
                                        .put("license", "")
                                        .put("useLocalLicenseKey", "true")
                                        .build())
                                .build())
                        .build());
    }
}
