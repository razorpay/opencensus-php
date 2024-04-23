const { read } = require('@changesets/config');
const { getPackages } = require('@manypkg/get-packages');
const { executePrompt } = require('../actions/prompt');
const { modifyTsConfigBasedNxGraph } = require('../actions/configGraph');
const { getNxChangelog, getDependencyGraph } = require('../actions/nxUtils');
const { prepareChangeset } = require('../actions/createChangeSet');
const {
  resetConfigAfterOperation,
  getFilesChangedPackages,
  getCurrentBranch,
} = require('../actions/gitUtils');
const { BASE_CONFIG_PATH } = require('../config');
const { getAffectedPackagesVersionType } = require('../actions/versionType');
const Logger = require('../actions/logger');

/**
 * This asynchronous function gets the configuration for the changeset.
 *
 * @param {Object} params - The parameters for the function.
 * @param {string} params.cwd - The current working directory.
 *
 * @returns {Object} The changeset configuration.
 */
const getConfig = async ({ cwd }) => {
  const packages = await getPackages(cwd);
  return read(cwd, packages);
};

/**
 * Retrieves the affected packages and dependency graph based on the provided head reference.
 * @param {Object} options - The options for retrieving the affected packages and graph.
 * @param {string} options.headRef - The head reference to use for retrieving the affected packages and graph.
 * @param {Object} options.config - changeset configuration.
 * @returns {Promise<Object>} - A promise that resolves to an object containing the affected projects and dependencies.
 */
const getAffectedPackagesAndGraph = async ({ headRef, config }) => {
  await modifyTsConfigBasedNxGraph({ type: 'exclude' });
  const { affectedProjects, dependencies } =
    (await getDependencyGraph({
      headRef,
      config,
    })) || {};
  resetConfigAfterOperation(BASE_CONFIG_PATH);
  if (!affectedProjects) {
    Logger.info('No affected projects available');
    process.exit(1);
  }
  return {
    affectedProjects,
    dependencies,
  };
};

/**
 * Constructs the payload for a changeset.
 * @param {Object} options - The options for constructing the changeset payload.
 * @param {Object} options.versionTypeMap - The map of package names to version types.
 * @param {Array<string>} options.affectedProjects - The names of the affected projects.
 * @param {Object} options.dependencies - The dependencies object.
 * @param {string} options.headRef - The head reference to use for retrieving the NX changelog.
 * @param {Object} options.config - changeset configuration.
 * @returns {Promise<Object>} - A promise that resolves to an object containing the summary and releases.
 */
const getChangesetPayload = async ({
  versionTypeMap,
  affectedProjects,
  dependencies,
  headRef,
  config,
}) => {
  const summary = getNxChangelog({
    headRef,
    config,
  });
  const releases = await getAffectedPackagesVersionType({
    versionTypeMap,
    dependencies,
    affectedProjects,
  });

  return {
    summary,
    releases,
  };
};

/**
 * Executes the release script with all child scripts.
 * @returns {Promise<void>} - A promise that resolves when the release script has been executed.
 */
(async function executeReleaseScript(cwd = process.cwd()) {
  const config = await getConfig({ cwd });
  const headRef = getCurrentBranch();
  if (config.baseBranch === headRef) {
    Logger.error('changesset creation failed, head and base branch should be different');
    process.exit(1);
  }

  const { changedPackages } = await getFilesChangedPackages({
    cwd,
    config,
  });

  if (!changedPackages.length) {
    Logger.warn('changeset is not applicable for these changes');
    process.exit(1);
  }

  const versionTypeMap = await executePrompt(changedPackages);
  const { affectedProjects, dependencies } = await getAffectedPackagesAndGraph({
    headRef,
    config,
  });

  if (!affectedProjects.length) {
    Logger.warn('No affected projects found, exiting...');
    process.exit(1);
  }

  const changesetPayload = await getChangesetPayload({
    versionTypeMap,
    affectedProjects,
    dependencies,
    headRef,
    config,
  });

  return prepareChangeset(changesetPayload, config, cwd);
})();
