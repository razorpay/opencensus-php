const fs = require('fs/promises');
const { DEPENDENCY_GRAPH_PATH } = require('../config');
const { runCommand } = require('./gitUtils');
const Logger = require('./logger');

/**
 * Fetches the affected packages in an Nx workspace.
 * @param {Object} params - The parameters for the function.
 * @param {string} params.headRef - The head reference for the git diff.
 * @returns {Object} - The affected packages.
 * @throws {Error} - If an error occurs while fetching the affected packages.
 */
// eslint-disable-next-line consistent-return
const getNxAffectedPackages = ({ headRef, baseRef }) => {
  try {
    const response = runCommand('npx nx show projects', [
      '--affected',
      '--json',
      `--base=${baseRef}`,
      `--head=${headRef}`,
    ]);
    return JSON.parse(response);
  } catch (error) {
    Logger.error(`Error in fetching nx graph: ${error}`);
    process.exit(0);
  }
};

/**
 * Sanitizes a changelog by removing unnecessary lines and formatting.
 * @param {string} changelog - The changelog to sanitize.
 * @returns {string} - The sanitized changelog.
 */
const sanitizeLog = (changelog) => {
  const log = changelog.split('\n');
  const sanitizedLog = log.map((line) => line.replace(/^\s*\+\s*/, '').trim()).filter(Boolean);

  return sanitizedLog.slice(2).join('\n').trim();
};

/**
 * Fetches the changelog for an Nx workspace based on affected packages between two git refs.
 * @param {Object} params - The parameters for the function.
 * @param {string} params.headRef - The head reference for the git diff.
 * @returns {string} - The changelog.
 * @throws {Error} - If an error occurs while fetching the changelog.
 */
// eslint-disable-next-line consistent-return
const getNxChangelog = ({ headRef, config: { baseBranch: baseRef } }) => {
  try {
    // added dummy version as a mandatory but removing version details from logs
    const response = runCommand('nx release changelog 0.0.1', [
      `--from=${baseRef}`,
      `--to=${headRef}`,
      '--dryRun',
    ]);
    Logger.info('Nx changelog generated successfully');
    return sanitizeLog(response);
  } catch (error) {
    Logger.error(`Error in fetching nx changelog: ${error}`);
    process.exit(0);
  }
};

/**
 * Fetches the dependency graph for an Nx workspace between two git refs and saves in output file.
 * @param {Object} params - The parameters for the function.
 * @param {string} params.headRef - The head reference for the git diff.
 * @param {Object} params.loader - The loader instance for the loading spinner.
 * @returns {Object} - The dependency graph.
 * @throws {Error} - If an error occurs while fetching the dependency graph.
 */
// eslint-disable-next-line consistent-return
const getDependencyGraphWithLoader = async ({ headRef, loader, baseRef }) => {
  const spinner = loader && loader.default('Generating nx graph').start();
  try {
    // resetting cache after ts config update
    runCommand('npx nx reset');
    runCommand('npx nx graph', [
      `--file=${DEPENDENCY_GRAPH_PATH}`,
      `--base=${baseRef}`,
      `--head=${headRef}`,
      '--affected=true',
    ]);

    try {
      const dependencyGraph = await fs.readFile(DEPENDENCY_GRAPH_PATH, 'utf8');
      const {
        graph: { dependencies },
        affectedProjects,
      } = JSON.parse(dependencyGraph);
      spinner && spinner.succeed('Nx graph generated successfully');
      Logger.info(`Nx graph output generated at: ${DEPENDENCY_GRAPH_PATH}`);

      return {
        affectedProjects,
        dependencies,
      };
    } catch (error) {
      spinner && spinner.fail('graph generation failed');
      Logger.error(`Error reading/parsing file: ${error}`);
      process.exit(0);
    }
  } catch (error) {
    spinner && spinner.fail('graph generation failed');
    Logger.error(`Error in fetching nx graph: ${error}`);
    process.exit(0);
  }
};

/**
 * Fetches the dependency graph by wrapping the script with loader.
 * @param {Object} params - The parameters for the function.
 * @param {string} params.headRef - The head reference for the git diff.
 * @returns {Object} - The dependency graph.
 * @throws {Error} - If an error occurs while fetching the dependency graph.
 */
const getDependencyGraph = async ({ headRef, config: { baseBranch: baseRef } }) => {
  let loader;
  try {
    loader = await import('ora');
  } catch (error) {
    Logger.error(`Error importing ora: ${error}`);
  }
  const graph = await getDependencyGraphWithLoader({
    headRef,
    loader,
    baseRef,
  });
  return graph;
};

module.exports = {
  getNxAffectedPackages,
  getNxChangelog,
  getDependencyGraph,
};
