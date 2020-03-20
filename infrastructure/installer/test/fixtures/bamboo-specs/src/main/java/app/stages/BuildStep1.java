package app.stages;

import com.atlassian.bamboo.specs.api.builders.task.Task;
import com.atlassian.bamboo.specs.builders.task.CheckoutItem;
import com.atlassian.bamboo.specs.builders.task.ScriptTask;
import com.atlassian.bamboo.specs.builders.task.VcsCheckoutTask;
import com.atlassian.bamboo.specs.model.task.ScriptTaskProperties;

public class BuildStep1 extends AbstractBuildStep {
    public BuildStep1() {
        super("BuildStep1", "Install dependencies", "JOB1");
    }

    protected Task<?, ?>[] getTasks() {
        return new Task<?, ?>[] {
                new VcsCheckoutTask()
                        .description("Checkout Default Repository")
                        .checkoutItems(new CheckoutItem().defaultRepository()),
                new ScriptTask()
                        .description("Give execution permissions")
                        .inlineBody("sudo chmod +rx infrastructure/bamboo/build/*/*.sh"),
                new ScriptTask()
                        .description("Composer install")
                        .location(ScriptTaskProperties.Location.FILE)
                        .fileFromPath("infrastructure/bamboo/build/install-dependencies/composer-install.sh"),
                new ScriptTask()
                        .description("Targz files")
                        .location(ScriptTaskProperties.Location.FILE)
                        .fileFromPath("infrastructure/bamboo/build/install-dependencies/tar-compress.sh")
        };
    }
}
