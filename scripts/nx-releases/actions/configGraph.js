const fs = require('fs/promises');
const { EXCLUDE_CONFIG_PATH, BASE_CONFIG_PATH } = require('../config');
const Logger = require('./logger');

// Function to remove comments from JSON data
const sanitizeJSON = (jsonString) => {
  return jsonString.replace(/\/\/(.*)|\/\*([\s\S]*?)\*\//g, '');
};

/**
 * Modifies the base tsconfig file based on the provided type.
 * @param {Object} options - The options for modifying the tsconfig.
 * @param {string} options.type - The type of modification to perform ('include' or 'exclude').
 * @returns {Promise<void>} - A promise that resolves when the tsconfig is modified successfully.
 * @throws {Error} - If there is an error in modifying the base tsconfig.
 */
const modifyTsConfigBasedNxGraph = async ({ type }) => {
  try {
    const excludePathRawData = await fs.readFile(EXCLUDE_CONFIG_PATH, 'utf-8');
    const { pathsToExcludeNxDependency } = JSON.parse(sanitizeJSON(excludePathRawData));

    const baseConfigRawData = await fs.readFile(BASE_CONFIG_PATH, 'utf-8');
    const baseConfig = JSON.parse(sanitizeJSON(baseConfigRawData));

    switch (type) {
      case 'include':
        baseConfig.compilerOptions.paths = {
          ...baseConfig.compilerOptions.paths,
          ...pathsToExcludeNxDependency,
        };
        break;
      default:
        Object.keys(pathsToExcludeNxDependency).forEach(
          (path) => delete baseConfig.compilerOptions.paths[path],
        );
        break;
    }

    await fs.writeFile(BASE_CONFIG_PATH, JSON.stringify(baseConfig, null, 2));
    Logger.success('Base tsconfig modified successfully to build graph ');
  } catch (error) {
    Logger.error(`Error in modifying base tsconfig: ${error}`);
    process.exit(1);
  }
};

module.exports = {
  modifyTsConfigBasedNxGraph,
};
