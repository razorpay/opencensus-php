const path = require('path');
const fs = require('fs-extra');
const { humanId } = require('human-id');
const { fetchChangedFiles } = require('./gitUtils');
const Logger = require('./logger');

/**
 * Returns the absolute path to the .changeset directory.
 * @param {string} cwd - The current working directory (default is process.cwd()).
 * @returns {string} - The absolute path to the .changeset directory.
 */
const getBasepath = (cwd = process.cwd()) => path.resolve(cwd, '.changeset');

/**
 * This function checks if a changeset file has been added in the current branch.
 *
 * @param {Object} config - The configuration object.
 * @param {string} config.baseBranch - The base branch to compare with the current branch.
 * @param {string} cwd - The current working directory.
 *
 * @returns {Array} An array of changed files that are changesets. A changeset file is considered to be any file that matches the pattern '.changeset/*.md' and is not the '.changeset/README.md' file.
 *
 * @example
 * const changesetAdded = checkIfChangeSetAdded({ config: { baseBranch: 'master' }, cwd: process.cwd() });
 * console.log(changesetAdded); // logs an array of changeset files added in the current branch since it diverged from master
 */
const checkIfChangeSetAdded = ({ config: { baseBranch: baseRef }, cwd }) => {
  const changedFiles = fetchChangedFiles({
    withBaseMerge: true,
    baseRef,
    cwd,
  });
  return changedFiles.filter(
    (files) => /^\.changeset\/.+\.md$/.test(files) && files !== '.changeset/README.md',
  );
};

/**
 * Returns the content of a changeset in markdown format.
 * @param {Object} changeset - The changeset to format.
 * @param {string} changeset.summary - The summary of the changeset.
 * @param {Array<Object>} changeset.releases - The releases in the changeset.
 * @returns {string} - The changeset content in markdown format.
 */
const getContentMarkdown = ({ summary, releases }) => {
  const changesetContent = `---
${releases.map((release) => `"${release.name}": ${release.type}`).join('\n')}
---

${summary}
`;
  return changesetContent;
};

/**
 * createNewChangeSet
 * Creates a new changeset file by creating markdown.
 * @param {Object} changeset - The changeset to create.
 * @returns {Promise<string>} - A promise that resolves with the ID of the created changeset.
 * @throws {Error} - If an error occurs while creating the changeset file.
 */
// eslint-disable-next-line consistent-return
const createNewChangeSet = async (changeset) => {
  try {
    const changesetBase = getBasepath();
    const changesetContent = getContentMarkdown(changeset);
    const changesetID = humanId({
      separator: '-',
      capitalize: false,
    });
    const newChangesetPath = path.resolve(changesetBase, `${changesetID}.md`);
    await fs.outputFile(newChangesetPath, changesetContent);
    Logger.success(`Changeset created successfully at ${newChangesetPath}`);
    return changesetID;
  } catch (error) {
    Logger.error(`Error creating changeset file: ${error}`);
    process.exit(1);
  }
};

/**
 * Modifies an existing changeset file if it already exists.
 * @param {Object} changeset - The changeset to modify.
 * @param {string} modifyPath - The path of the changeset file to modify.
 * @returns {Promise<string>} - A promise that resolves with the name of the modified changeset file.
 * @throws {Error} - If an error occurs while modifying the changeset file.
 */
// eslint-disable-next-line consistent-return
const modifyChangeSet = async (changeset, modifyPath) => {
  const changesetContent = getContentMarkdown(changeset);
  try {
    await fs.writeFile(modifyPath, changesetContent);
    Logger.success(`Changeset modified successfully at ${modifyPath}`);
    return path.basename(modifyPath);
  } catch (error) {
    Logger.error(`Error modifying to file: ${error}`);
    process.exit(1);
  }
};

/**
 * Prepares a changeset by either modifying an existing changeset file or creating a new one.
 * @param {Object} changeset - The changeset to prepare.
 * @returns {Promise<string>} - A promise that resolves with the ID of the prepared changeset.
 * @throws {Error} - If an error occurs while preparing the changeset.
 */
const prepareChangeset = async (changeset, config, cwd) => {
  const isChangeSetAdded = checkIfChangeSetAdded({
    config,
    cwd,
  });
  if (isChangeSetAdded.length > 1) {
    Logger.error(
      `should have only one changeset file: found multiple, please keep single changeset`,
    );
    process.exit(1);
  }

  if (isChangeSetAdded.length === 1) {
    const changesetID = await modifyChangeSet(changeset, isChangeSetAdded[0]);
    return changesetID;
  }

  const changesetID = await createNewChangeSet(changeset);
  return changesetID;
};

module.exports = {
  prepareChangeset,
};
