const HEAD_REF = 'HEAD';
const EXCLUDE_CONFIG_PATH = 'tsconfig.pathsToExclude.json';
const BASE_CONFIG_PATH = 'tsconfig.base.json';

const DEPENDENCY_GRAPH_PATH = 'scripts/nx-releases/output.json';

const VERSIONS = ['patch', 'minor', 'major'];
const VERSIONS_ORDER = {
  major: 3,
  minor: 2,
  patch: 1,
};

const ROOT_DIR = 'dashboard';

module.exports = {
  HEAD_REF,
  EXCLUDE_CONFIG_PATH,
  BASE_CONFIG_PATH,
  DEPENDENCY_GRAPH_PATH,
  VERSIONS,
  ROOT_DIR,
  VERSIONS_ORDER,
};
