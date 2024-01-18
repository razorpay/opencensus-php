// Original taken from: web/node_modules/@razorpay/universe-test/src/configs/e2e.web/infraUtils.js
// removed all external package dependencies
const fs = require('fs');
const path = require('path');

async function triggerJob(payload) {
  const url = process.env.JOB_URL;
  const jobToken = process.env.ARGO_TOKEN;

  const headers = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  };

  if (jobToken) {
    headers.Authorization = `Bearer ${jobToken}`;
  }

  try {
    const body = JSON.stringify(payload);
    const res = await fetch(url, {
      method: 'POST',
      body,
      headers,
    });
    console.log('triggerJob', { body });
    const data = await res.json();
    if (!res.ok) {
      throw new Error(data.message);
    }
    console.log('[universe-test]: job succeeded', data);
  } catch (error) {
    console.log('[universe-test]: job failed', error);
  }
}

function getDevstackConfigPath() {
  const rootPath = process.cwd();
  console.log(rootPath);
  const configPath = path.join(rootPath, 'web', 'config', 'devstack.json');

  return configPath;
}

function getDevstackConfigContent() {
  const configPath = getDevstackConfigPath();
  const configContent = fs.readFileSync(configPath, 'utf-8');
  const depCommits = JSON.parse(configContent);

  return depCommits;
}

function getDependencies(depCommits) {
  const entries = Object.entries(depCommits);

  const dependencies = entries.map(([depName, commit]) => {
    const dependency = {
      name: depName,
      commit_id: commit,
    };

    // TODO: Make this changes dyamic from depCommits payload;
    if (depName === 'api') {
      dependency.chart_values = {
        enable_edge_base: true,
      };
    }
    return dependency;
  });

  return dependencies;
}

function getConfig() {
  const repository = process.env.GITHUB_REPOSITORY;
  const repoName = process.env.REPO_NAME;
  const pullNumber = process.env.PR_NUMBER;
  const author = process.env.GITHUB_ACTOR?.toLowerCase();
  const selfCommit = process.env.COMMIT_ID;

  return {
    repository,
    repoName,
    pullNumber,
    author,
    selfCommit,
  };
}

async function devstackDeploy() {
  const { repository, repoName, pullNumber, selfCommit } = getConfig();

  const depCommits = await getDevstackConfigContent();
  const dependencies = getDependencies(depCommits);

  const payload = {
    repository,
    pull_request_number: pullNumber,
    self: {
      name: repoName,
      commit_id: selfCommit,
    },
    dependencies,
  };

  await triggerJob(payload);
}

async function runE2ETests() {
  const { repository, repoName, pullNumber, author, selfCommit } = getConfig();

  const depCommits = await getDevstackConfigContent();
  const dependencies = getDependencies(depCommits);
  const deployData = process.env.IS_MASTER
    ? {}
    : {
        pull_request_number: pullNumber,
        devstack_url: `https://${repoName}-pr-${pullNumber}.dev.razorpay.in`,
      };

  const payload = {
    ...deployData,
    repository,
    author,
    self: {
      name: repoName,
      commit_id: selfCommit,
    },
    dependencies,
  };

  await triggerJob(payload);
}

async function devstackRevert() {
  const depCommits = await getDevstackConfigContent();
  const emptyCommits = {};
  Object.keys(depCommits).forEach((key) => {
    emptyCommits[key] = '';
  });
  const newConfigContent = `${JSON.stringify(emptyCommits, null, 2)}\n`;
  const configPath = getDevstackConfigPath();
  await fs.writeFile(configPath, newConfigContent, 'utf-8');
}

module.exports = {
  devstackDeploy,
  devstackRevert,
  runE2ETests,
};
