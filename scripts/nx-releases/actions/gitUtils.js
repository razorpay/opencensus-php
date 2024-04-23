const path = require('path');
const { execSync } = require('child_process');
const micromatch = require('micromatch');
const { getPackages } = require('@manypkg/get-packages');
const isSubdir = require('is-subdir');
const { HEAD_REF } = require('../config');
const Logger = require('./logger');

/**
 * Executes a shell command and returns the output.
 * @param {string} command - The command to execute.
 * @param {Array<string>} args - The arguments to pass to the command.
 * @returns {string} - The stdout from the command execution.
 */
const runCommand = (command, args = []) => {
  const argList = args.join(' ');
  const stdout = execSync(`${command} ${argList}`, { encoding: 'utf8' });
  return stdout;
};

/**
 * Returns the name of the current Git branch.
 * @returns {string} - The name of the current Git branch.
 * @throws {Error} - If an error occurs while getting the branch name.
 */
// eslint-disable-next-line consistent-return
const getCurrentBranch = () => {
  try {
    const ref = runCommand('git', ['rev-parse', '--abbrev-ref', HEAD_REF]);
    return ref.toString().trim();
  } catch (error) {
    Logger.error(`error getting current branch name: ${error.stderr.toString()}`);
    process.exit(1);
  }
};

/**
 * This function gets the commit where the current HEAD diverged from the given reference (ref).
 *
 * @param {string} cwd - The current working directory.
 * @param {string} ref - The reference to compare with the current HEAD.
 *
 * @returns {string} The commit hash where the current HEAD diverged from the given reference.
 * If the reference does not exist or an error occurs, the process will exit with a status code of 1.
 *
 * @example
 * const divergedCommit = getDivergedCommit(process.cwd(), 'origin/master');
 * console.log(divergedCommit); // logs the commit hash where the current HEAD diverged from 'origin/master'
 */
// eslint-disable-next-line consistent-return
const getDivergedCommit = (cwd, ref) => {
  try {
    const cmd = runCommand('git', ['merge-base', ref, HEAD_REF], { cwd });
    return cmd.trim();
  } catch (error) {
    Logger.error(`Failed to find where HEAD diverged from ${ref}. Does ${ref} exist?`);
    process.exit(1);
  }
};

/**
 * This function gets the root directory of the current Git workspace.
 *
 * @param {string} cwd - The current working directory.
 * @returns {string} The root directory of the current Git workspace.
 */
// eslint-disable-next-line consistent-return
const getWorkspaceRoot = (cwd) => {
  try {
    const cmd = runCommand('git', ['rev-parse', '--show-toplevel'], { cwd });
    return cmd.trim();
  } catch (error) {
    Logger.error(`Failed to find workspace root`);
    process.exit(1);
  }
};

/**
 * This function fetches the files that have changed since the current HEAD diverged from a given reference (baseRef).
 *
 * @param {Object} params - The parameters for the function.
 * @param {boolean} params.withBaseMerge - If true, the function will find changes since the common ancestor of the current HEAD and the baseRef.
 * @param {boolean} [params.fullPath=false] - If true, the function will return the full path of the changed files. Otherwise, it will return the files only.
 * @param {string} params.baseRef - The reference to compare with the current HEAD.
 * @param {string} params.cwd - The current working directory.
 *
 * @returns {Array} An array of changed files. If fullPath is true, the array will contain the full paths of the files. Otherwise, it will contain the file names only.
 * If an error occurs, the process will exit with a status code of 1.
 */
// eslint-disable-next-line consistent-return
const fetchChangedFiles = ({ withBaseMerge, fullPath = false, baseRef, cwd }) => {
  const divergedAt = getDivergedCommit(cwd, baseRef);
  try {
    const execParams = ['diff', '--name-only'];
    if (withBaseMerge) {
      execParams.push(`$(git merge-base ${divergedAt} ${HEAD_REF})`);
    } else {
      execParams.push(divergedAt);
    }
    const output = runCommand('git', execParams, { cwd });
    const files = output.trim().split('\n').filter(Boolean);

    if (!fullPath) {
      return files;
    }
    const workspaceRoot = getWorkspaceRoot({ cwd });
    return files.map((file) => path.resolve(workspaceRoot, file));
  } catch (error) {
    Logger.error(`Failed to diff against ${divergedAt}. Is ${divergedAt} a valid ref?`);
    process.exit(1);
  }
};

/**
 * Resets a ts config back to its state in the Git index after generating nx graph.
 * @param {string} filePath - The path of the file to reset.
 * @throws {Error} - If an error occurs while resetting the file.
 */
const resetConfigAfterOperation = (filePath) => {
  try {
    execSync(`git checkout ${filePath}`, { stdio: 'inherit' });
    Logger.info('Base tsconfig checkout successful after operation');
  } catch (error) {
    Logger.error(`Error resetting ${filePath} config after operation : ${error}`);
    process.exit(0);
  }
};

/**
 * This asynchronous function gets the packages that have files changed since the current HEAD diverged from a given reference (baseBranch).
 *
 * @param {Object} params - The parameters for the function.
 * @param {string} params.cwd - The current working directory.
 * @param {Object} params.config - changeset configuration.
 * @param {string} params.config.baseBranch - The base branch to compare with the current branch.
 * @param {Array} [params.changedFilePatterns=["**"]] - The patterns to match the changed files. By default, it matches all files.
 *
 * @returns {Object} An object containing an array of changed packages. Each package is an object that includes the package's directory and its package.json data.
 * The function also logs the names of the packages with changes and the total number of packages with changes.
 *
 */
const getFilesChangedPackages = async ({ cwd, config, changedFilePatterns = ['**'] }) => {
  const modifiedFiles = fetchChangedFiles({
    withBaseMerge: false,
    fullPath: true,
    cwd,
    baseRef: config.baseBranch,
  });
  const { packages = [] } = await getPackages(cwd);
  const changedPackages = packages
    .sort((packageA, packageB) => packageB.dir.length - packageA.dir.length)
    .filter((pkg) => {
      const modifiedFilesInPackage = [];
      for (let index = modifiedFiles.length - 1; index >= 0; index--) {
        const file = modifiedFiles[index];
        if (isSubdir(pkg.dir, file)) {
          modifiedFiles.splice(index, 1);
          const relativeFilePath = file.slice(pkg.dir.length + 1);
          modifiedFilesInPackage.push(relativeFilePath);
        }
      }
      return (
        modifiedFilesInPackage.length > 0 &&
        micromatch(modifiedFilesInPackage, changedFilePatterns).length > 0
      );
    });

  Logger.success(`Packages with changes:`);
  Logger.list(changedPackages.map((pkg) => pkg.packageJson.name));
  Logger.info(`Total ${changedPackages.length} packages with changes detected.`);

  return { changedPackages };
};

module.exports = {
  runCommand,
  fetchChangedFiles,
  resetConfigAfterOperation,
  getFilesChangedPackages,
  getCurrentBranch,
};
