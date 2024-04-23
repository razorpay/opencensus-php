const { getPackages } = require('@manypkg/get-packages');
const { VERSIONS_ORDER } = require('../config');
const Logger = require('./logger');

/**
 * Constructs a dependency tree from a dependencies object.
 * @param {Object} dependencies - The dependencies object.
 * @returns {Object} - The dependency tree.
 */
const getDependencyTree = async (dependencies, cwd = process.cwd()) => {
  const { packages } = await getPackages(cwd);
  const packageDirMapping = packages.reduce((accumulator, each) => {
    const {
      dir,
      packageJson: { name },
    } = each;
    const dirName = dir.split('/').pop();
    accumulator[dirName] = name;
    return accumulator;
  }, {});
  return Object.keys(dependencies).reduce((accumulator, each) => {
    const dependency = packageDirMapping[each] || each;
    if (accumulator[dependency]) {
      accumulator[dependency].push(dependency);
    } else {
      accumulator[dependency] = [dependency];
    }
    dependencies[each].forEach((subEach) => {
      const subDependency = packageDirMapping[subEach.target] || subEach.target;
      if (subEach.type === 'static') {
        if (accumulator[subDependency]) {
          accumulator[subDependency].push(dependency);
        } else {
          accumulator[subDependency] = [dependency];
        }
      }
    });
    return accumulator;
  }, {});
};

/**
 * Modifies the versions of the affected projects based on precedence from leaf to root in dependency tree.
 * @param {Object} affectedProjectsVersions - The versions of the affected projects.
 * @param {string} versionType - The type of the version.
 * @param {Array<string>} dependencyTree - The dependency tree.
 */
const modifyVersionByPrecedence = (affectedProjectsVersions, versionType, dependencyTree) => {
  for (const dependency of dependencyTree) {
    if (
      !affectedProjectsVersions[dependency] ||
      (affectedProjectsVersions[dependency] &&
        VERSIONS_ORDER[affectedProjectsVersions[dependency]] < VERSIONS_ORDER[versionType])
    ) {
      affectedProjectsVersions[dependency] = versionType;
    }
  }
};

/**
 * Calculates the version type for each affected package.
 * @param {Object} params - The parameters for the function.
 * @param {Object} params.versionTypeMap - The map of package names to version types.
 * @param {Object} params.dependencies - The dependencies object.
 * @returns {Promise<Array<Object>>} - A promise that resolves with an array of objects, each containing a package name and its version type.
 */
const getAffectedPackagesVersionType = async ({ versionTypeMap, dependencies }) => {
  Logger.info('Calculating affected packages and their version type');
  const affectedProjectsVersions = {};
  const dependencyTree = await getDependencyTree(dependencies);

  const changedPackages = Object.keys(versionTypeMap);
  for (const packageName of changedPackages) {
    modifyVersionByPrecedence(
      affectedProjectsVersions,
      versionTypeMap[packageName],
      dependencyTree[packageName] || [],
    );
  }

  Logger.info('Affected packages and their version type calculated successfully');

  return Object.keys(affectedProjectsVersions).reduce((accumulator, each) => {
    accumulator.push({
      name: each,
      type: affectedProjectsVersions[each],
    });
    return accumulator;
  }, []);
};

module.exports = {
  getAffectedPackagesVersionType,
};
