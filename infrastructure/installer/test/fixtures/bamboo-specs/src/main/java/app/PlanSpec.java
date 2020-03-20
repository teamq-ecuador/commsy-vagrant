package app;

import app.stages.BuildStep1;
import app.stages.BuildStep2;
import app.stages.BuildStep3;
import com.atlassian.bamboo.specs.api.BambooSpec;
import com.atlassian.bamboo.specs.api.builders.BambooKey;
import com.atlassian.bamboo.specs.api.builders.Variable;
import com.atlassian.bamboo.specs.api.builders.permission.PermissionType;
import com.atlassian.bamboo.specs.api.builders.permission.Permissions;
import com.atlassian.bamboo.specs.api.builders.permission.PlanPermissions;
import com.atlassian.bamboo.specs.api.builders.plan.Plan;
import com.atlassian.bamboo.specs.api.builders.plan.PlanIdentifier;
import com.atlassian.bamboo.specs.api.builders.plan.branches.BranchCleanup;
import com.atlassian.bamboo.specs.api.builders.plan.branches.PlanBranchManagement;
import com.atlassian.bamboo.specs.api.builders.plan.configuration.ConcurrentBuilds;
import com.atlassian.bamboo.specs.api.builders.project.Project;
import com.atlassian.bamboo.specs.api.builders.repository.VcsChangeDetection;
import com.atlassian.bamboo.specs.builders.repository.git.GitRepository;
import com.atlassian.bamboo.specs.builders.repository.git.UserPasswordAuthentication;
import com.atlassian.bamboo.specs.builders.trigger.BitbucketServerTrigger;
import com.atlassian.bamboo.specs.util.BambooServer;

@BambooSpec
public class PlanSpec {

    public Plan plan() {
        final Plan plan = new Plan(new Project()
                .key(new BambooKey("##Project_Name_Short##"))
                .name("##Project_Name##"),
                "Service",
                new BambooKey("SER"))
                .enabled(true)
                .pluginConfigurations(new ConcurrentBuilds())
                .stages(
                        new BuildStep1(),
                        new BuildStep2(),
                        new BuildStep3()
                )
                .linkedRepositories("##Service_Name##")

                .triggers(new BitbucketServerTrigger())
                .variables(new Variable("AWS_DEPLOY_ROOT_PATH",
                                "/var/deploy/##Service_Deploy_Path##"),
                        new Variable("AWS_ECR",
                                "088242704549.dkr.ecr.eu-central-1.amazonaws.com"),
                        new Variable("AWS_TESTING_MASTER_USERNAME",
                                "ubuntu"))
                .planBranchManagement(new PlanBranchManagement()
                        .delete(new BranchCleanup())
                        .notificationForCommitters());
        return plan;
    }

    public PlanPermissions planPermission() {
        final PlanPermissions planPermission = new PlanPermissions(new PlanIdentifier("PAYM", "SER"))
                .permissions(new Permissions()
                        .userPermissions("aroddis", PermissionType.BUILD, PermissionType.CLONE, PermissionType.ADMIN, PermissionType.VIEW, PermissionType.EDIT)
                        .groupPermissions("Giffits Entwickler", PermissionType.BUILD, PermissionType.CLONE, PermissionType.VIEW)
                        .groupPermissions("Giffits Admin", PermissionType.ADMIN, PermissionType.EDIT)
                        .loggedInUserPermissions(PermissionType.VIEW));
        return planPermission;
    }

    public static void main(String... argv) {
        //By default credentials are read from the '.credentials' file.
        BambooServer bambooServer = new BambooServer("http://gifham017:8085");

        final GitRepository gitRepository = new GitRepository()
                .name("##Service_Name##")
                .url("##Repository_Uri##")
                .branch("master")
                .authentication(new UserPasswordAuthentication("##Repository_Username##")
                        .password("##Repository_Password##"))
                .changeDetection(new VcsChangeDetection());
        bambooServer.publish(gitRepository);

        final PlanSpec planSpec = new PlanSpec();

        final Plan plan = planSpec.plan();
        bambooServer.publish(plan);

        final PlanPermissions planPermission = planSpec.planPermission();
        bambooServer.publish(planPermission);
    }
}
