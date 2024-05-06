// Original taken from: web/node_modules/@razorpay/universe-test/src/configs/e2e.web/infraUtils.js
// removed all external package dependencies
const fs = require('fs');
const path = require('path');
// const { execSync } = require('child_process');

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
    console.log('[universe-cli]: job succeeded', data);
  } catch (error) {
    console.log('[universe-cli]: job failed', error);
  }
}

function getDevstackConfigPath() {
  const rootPath = process.cwd();
  console.log(rootPath);
  const configPath = path.join(rootPath, 'playwright', 'config', 'devstack.json');

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
  const headRef = process.env.GITHUB_HEAD_REF || '^HEAD';
  const affectedProjects = process.env.AFFECTED_PROJECTS;
  return {
    repository,
    repoName,
    pullNumber,
    author,
    selfCommit,
    headRef,
    affectedProjects,
  };
}

function nxAffected(affectedProjects) {
  // Using affectedProjects from env in case of CI
  return affectedProjects.trim().split(',');

  // TIP: Script to get affected projects, For reference only or if you want to use it in local
  // const args = ['--affected', '--json', `--base=origin/master`, `--head=origin/${headRef}`];
  // const stdout = execSync(`npx nx show projects ${args.join(' ')}`, { encoding: 'utf8' });
  // return JSON.parse(stdout);
}

const getSelfPayload = ({ repoName, selfCommit, affectedProjects }) => {
  const nxAffectedList = nxAffected(affectedProjects);
  console.log('nxAffectedList', nxAffectedList);
  const getCommit = (project) => (nxAffectedList.indexOf(project) > -1 ? selfCommit : 'latest');

  const payload = {
    name: repoName,
    // commit_id: getCommit('web'),
    commit_id: selfCommit,
    chart_values: {
      self_serve_image: getCommit('self-serve'),
      web_requests_memory: '350Mi',
      web_requests_cpu: '500m',
    },
  };
  return payload;
};

async function devstackDeploy() {
  const { repository, repoName, pullNumber, selfCommit, affectedProjects } = getConfig();

  const depCommits = await getDevstackConfigContent();
  const dependencies = getDependencies(depCommits);

  const self = getSelfPayload({
    repoName,
    selfCommit,
    affectedProjects,
  });

  const payload = {
    repository,
    pull_request_number: pullNumber,
    self,
    dependencies,
  };

  await triggerJob(payload);
}

async function runE2ETests() {
  const { repository, repoName, pullNumber, author, selfCommit, affectedProjects } = getConfig();

  const depCommits = await getDevstackConfigContent();
  const dependencies = getDependencies(depCommits);
  const deployData = process.env.IS_MASTER
    ? {}
    : {
        pull_request_number: pullNumber,
        devstack_url: `https://${repoName}-pr-${pullNumber}.dev.razorpay.in`,
      };

  const self = getSelfPayload({
    repoName,
    selfCommit,
    affectedProjects,
  });
  const payload = {
    ...deployData,
    repository,
    author,
    self,
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
