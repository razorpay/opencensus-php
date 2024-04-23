const { modifyTsConfigBasedNxGraph } = require('../actions/configGraph');

(async function executeReleaseScript() {
  const isExclude = process.env.EXCLUDE;
  await modifyTsConfigBasedNxGraph({ type: isExclude ? 'exclude' : 'include' });
})();
