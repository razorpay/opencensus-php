const Enquirer = require('enquirer');
const { VERSIONS } = require('../config');
const Logger = require('./logger');

/**
 * Prompts the user to select the type of changes for each changed package.
 * @param {Array<string>} changedPackages - The names of the changed packages.
 * @returns {Promise<Object>} - A promise that resolves with the user's selections.
 * @throws {Error} - If an error occurs while taking input or the prompt is closed forcefully.
 */
// eslint-disable-next-line consistent-return
const executePrompt = async (changedPackages) => {
  try {
    const bumpedVersions = VERSIONS.map((type) => ({ type }));

    const promptForm = changedPackages.map(({ packageJson: { name: packageName } }) => ({
      name: packageName,
      type: 'select',
      message: `What type of changes is this for ${packageName} package?`,
      choices: bumpedVersions.map((each) => {
        return {
          name: each.type,
          message: `=> ${each.type}`,
        };
      }),
    }));

    const promptInput = await Enquirer.prompt(promptForm);

    Logger.success('Package version type selected successfully');
    Logger.table(
      Object.entries(promptInput).map(([packageName, versionType]) => ({
        packageName,
        versionType,
      })),
    );

    return promptInput;
  } catch (error) {
    Logger.error(`Error occurred while taking input or prompt closed forcefully: ${error}`);
    process.exit(1);
  }
};

module.exports = {
  executePrompt,
};
