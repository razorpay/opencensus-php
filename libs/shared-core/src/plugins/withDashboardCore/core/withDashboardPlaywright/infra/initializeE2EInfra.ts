import { BASE_DEPENDENCIES } from '../constants';
import { getConfig } from './getConfig';
import { getDevstackConfigContent } from './getDevstackConfigContent';
import { getDependencies } from './getDependencies';
import { getSelfPayload } from './getSelfPayload';
import { triggerJob } from './triggerJob';
import { waitForInfraDeployment } from './waitForInfraDeployment';

export const initializeE2EInfra = async () => {
  try {
    const {
      repository,
      repoName,
      author,
      selfCommit,
      affectedProjects,
      assigneddevstackLabel,
      intervalMinutes,
      servicesTTL,
    } = getConfig();

    if (!assigneddevstackLabel) {
      console.error(
        '[@libs/dashboard-core]: Assigned devstack label is missing in the environment',
      );
      process.exit(1);
    }

    const depCommits = getDevstackConfigContent();
    const dependencies = getDependencies(depCommits);

    const self = await getSelfPayload({
      repoName,
      selfCommit,
      affectedProjects,
    });

    /**
     * @warning Remove this once the devstack changes are merged
     */

    const payload = {
      workflow_name: process.env.ASSIGNED_ARGO_WORKFLOW_NAME,
      ttl: servicesTTL,
      commit_id: selfCommit,
      devstack_label: assigneddevstackLabel,
      repository,
      kube_manifests_ref: 'master',
      author,
      self,
      dependencies,
      skip_check_base_deployment_status: 'false',
      base_dependencies: BASE_DEPENDENCIES,
    };

    await triggerJob(payload);

    await waitForInfraDeployment({
      intervalMinutes,
    });
  } catch (e) {
    console.error('[@libs/dashboard-core]: Job Failed', e instanceof Error ? e.message : e);
    process.exit(1);
  }
};
