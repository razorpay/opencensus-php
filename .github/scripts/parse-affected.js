const { existsSync, readdirSync } = require('fs');
const os = require('os');

const affectedProjectsViaArgs = process.argv[2] || '';
const affectedProjectsArray = affectedProjectsViaArgs
  .split(',')
  .map((p) => p.trim())
  .filter(Boolean);

const totalOnboardedMicroApps = readdirSync('apps', { withFileTypes: true })
  .filter((entry) => entry.isDirectory())
  .map((entry) => entry.name);

// Not in use for now. To be used after implementation of publishable libs
let affected_libs = [];

// Payments dashboard will remain P0 till the build time matches other apps (Long goal)
let affected_apps_p0 = [];

// Rest apps under web/js/* will be considered as P1
let affected_apps_p1 = [];

// All apps under dir will be P2
let affected_apps_p2 = [];

if (!Array.isArray(affectedProjectsArray) || !Boolean(affectedProjectsArray.length)) {
  throw new Error('Invalid Configuration: No projects affected?!');
}

const validateMicroapp = (p) => {
  if (!/^[a-z]+(-[a-z]+){0,1}$/.test(p)) {
    throw new Error(
      `Invalid project name "${p}". App name should be in lowercase letters and if required separated by hyphens alone, with a max of two words.`,
    );
  } else {
    return true;
  }
};

const sortArrayWithAtSymbol = (arr, order = 'asc') => {
  arr.sort((a, b) => {
    if (a.startsWith('@') && b.startsWith('@')) {
      return order === 'asc' ? a.localeCompare(b) : b.localeCompare(a);
    } else if (a.startsWith('@')) {
      return -1;
    } else if (b.startsWith('@')) {
      return 1;
    }
    return order === 'asc' ? a.localeCompare(b) : b.localeCompare(a);
  });
};

totalOnboardedMicroApps.map(validateMicroapp);

// Base line for trigger priority of build workflow (Exception: Shell Server)
for (const p of affectedProjectsArray) {
  if (p.startsWith('@libs/')) {
    affected_libs.push(p);
  } else if (p === 'payments-dashboard') {
    affected_apps_p0.push(p);
  } else if (p.endsWith('-dashboard') && !['partner-dashboard', 'pokedex-dashboard', 'tnc-dashboard'].includes(p)) {
    affected_apps_p1.push(p);
  } else if (existsSync(`apps/${p}`)) {
    // Validate project naming convention
    // 1. Should be lower case & alphanumeric
    // 2. Should be only separated by -
    // 3. Should contain only two words (To keep convention norms)
    if (validateMicroapp(p)) {
      affected_apps_p2.push(p);
    }
  }
}

const affectedAll = [
  ...affected_libs,
  ...affected_apps_p0,
  ...affected_apps_p1,
  ...affected_apps_p2,
];

sortArrayWithAtSymbol(affected_libs);
sortArrayWithAtSymbol(affected_apps_p0);
sortArrayWithAtSymbol(affected_apps_p1);
sortArrayWithAtSymbol(affected_apps_p2);
sortArrayWithAtSymbol(affectedAll);

console.log(`affected_libs=${JSON.stringify(affected_libs)}`);
console.log(`affected_apps_p0=${JSON.stringify(affected_apps_p0)}`);
console.log(`affected_apps_p1=${JSON.stringify(affected_apps_p1)}`);
console.log(`affected_apps_p2=${JSON.stringify(affected_apps_p2)}`);

// To be used for other edge case scenarios
console.log(`affected_all=${JSON.stringify(affectedAll)}`);

console.log(
  `affected_apps=${JSON.stringify([
    ...affected_apps_p0,
    ...affected_apps_p1,
    ...affected_apps_p2,
  ])}`,
);

// To be passed to shell server
console.log(`onboarded_microapps=${totalOnboardedMicroApps.join(',')}`);

// ############################################ Code Integrity Flow ################################################################

const utConfiguredProjectsViaArgs = process.argv[3] || '';

const utConfiguredAffectedApps = utConfiguredProjectsViaArgs
  .split(',')
  .map((p) => p.trim())
  .filter((e) => Boolean(e) && affectedProjectsArray.includes(e));

const resourceHeavyApps = ['payments-dashboard']; // P0 apps

// P0
/**
 * Apps that takes a huge time (More than 10mins) to run UTs or Eslint or anything on should be considered under this scope
 * These will have its own matrix, with each matrix having multiple jobs based on shards configured.
 * Each shard's job will utilize the max available cores on the runner.
 */
const getMemoryIntensiveAppsUTSetup = () => {
  const totalShardsForMemoryIntensiveApps = 8;
  const memoryIntensiveAppsShards = Array.from(
    { length: totalShardsForMemoryIntensiveApps },
    (_, i) => i + 1,
  );

  const memoryIntensiveApps = utConfiguredAffectedApps.filter((app) =>
    resourceHeavyApps.includes(app),
  );

  console.log(`memory_intensive_ut_configured_apps=${JSON.stringify(memoryIntensiveApps)}`);

  // [NOTE] Not Implemented: Keeping for now, will be used in future
  console.log(
    `memory_intensive_ut_configured_apps_shards_array=${JSON.stringify(memoryIntensiveAppsShards)}`,
  );
  console.log(
    `memory_intensive_ut_configured_apps_shards_total=${totalShardsForMemoryIntensiveApps}`,
  );
};

/**
 * Apps that takes relatively less time (0-10 mins MAX) to run UTs or Eslint or anything on should be considered under this scope
 * Mechanism:
 *  - shard_set = 2
 *  - Will generate a matrix of n sets of apps, n = available_cores / shard_set
 *  - Each app will have consume {{ shard_set }} cores
 */
const getDefaultAppsUTSetup = () => {
  const totalCores = os.cpus().length;
  const maxAppsPerSet = Math.floor(totalCores / 4);

  const p1Apps = utConfiguredAffectedApps.filter((app) => !resourceHeavyApps.includes(app));

  // Distribute apps across sets
  const sets = [];
  for (let i = 0; i < p1Apps.length; i += maxAppsPerSet) {
    sets.push(p1Apps.slice(i, i + maxAppsPerSet).join(','));
  }

  // Log the results
  console.log(`default_ut_configured_apps_matrix=${JSON.stringify(sets)}`);
};

getMemoryIntensiveAppsUTSetup();
getDefaultAppsUTSetup();

const playwrightConfiguredProjectsViaArgs = process.argv[4] || '';

const playwrightConfiguredApps = playwrightConfiguredProjectsViaArgs
  .split(',')
  .map((p) => p.trim())
  .filter((e) => Boolean(e) && affectedProjectsArray.includes(e));

console.log(
  `playwright_configured_affected_apps_comma_sep=${JSON.stringify(playwrightConfiguredApps.join(','))}`,
);

console.log(
  `playwright_configured_affected_apps_array=${JSON.stringify(playwrightConfiguredApps)}`,
);
