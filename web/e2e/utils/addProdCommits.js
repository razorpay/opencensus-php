const fs = require('fs');
const path = require('path');

const prodCommitIdFetchURLMap = {
  api: 'https://api.razorpay.com/commit.txt',
  'payment-links': 'https://paymentlinks-test.razorpay.com/commit.txt',
  gimli: 'https://rzp.io/commit.txt',
  // TODO: Devstack kept in sync with prod
  reminders: 'https://reminders.dev.razorpay.in/commit.txt',
  terminals: 'https://terminals-live.dev.razorpay.in/status',
  scrooge: 'https://scrooge.razorpay.com/commit.txt',
  // dashboard: 'https://dashboard.razorpay.com/commit.txt',
  // 'master-onboarding': 'https://master-onboarding.dev.razorpay.in/commit.txt',
  // 'banking-account': 'https://banking-account.dev.razorpay.in/commit.txt',
  pgos: 'https://pgos.concierge.razorpay.com/commit.txt',
};

const fetcher = async (url, method = 'GET') => {
  const response = await fetch(url, { method });

  const contentType = response.headers.get('content-type');
  let res;
  if (contentType.includes('application/json')) {
    res = await response.json();
  } else if (contentType.includes('text/plain')) {
    res = await response.text();
  } else {
    throw new Error('Invalid response type');
  }
  return res;
};

const fetchProdCommitId = async (serviceName) => {
  let commitId = '';
  try {
    const prodCommitIdURL = prodCommitIdFetchURLMap[serviceName];
    const res = await fetcher(prodCommitIdURL);

    if (serviceName === 'terminals') {
      // terminals returns with application/json type response
      commitId = res.commit_id.split(',')[0];
    } else if (serviceName === 'payment-links') {
      commitId = res.commit;
    } else {
      commitId = res;
    }
    commitId = commitId.replaceAll('\n', '');
    commitId = commitId.replaceAll('\r', '');
    console.log(`${serviceName} commit:`, commitId);
  } catch (error) {
    console.log(
      `fetching prod commit failed for ${serviceName}:`,
      error?.response?.data,
      error?.response?.status,
    );
    // return empty string so that argo uses commit from master
    return '';
  }
  return commitId;
};

const shouldFetchProdCommitId = (serviceName, commitId) => {
  const isCommitIdFetchURLPresent = serviceName in prodCommitIdFetchURLMap;
  const isCommitIdPresent = !!commitId;

  return !isCommitIdPresent && isCommitIdFetchURLPresent;
};

// TODO: Use this commit after upgrading universe-test https://github.com/razorpay/dashboard/tree/fa63f64a1a1fb090187da71e7c7e606039473861
// If commit_id key is empty string -> Use production commit if available, else use latest master commit (done via argo)
// If commit_id key is present -> Use commit_id specified (applicable only for dependencies array)
// Note: Add dependencies to devstack.json only if they need to be deployed additionally (e.g. if base pods aren't deployed)

async function updateDevstackJSONWithProdCommits() {
  let response = '';
  try {
    const rootPath = process.cwd();
    const devstackJSONPath = path.join(rootPath, 'web/config/devstack.json');
    const devstackJSONContent = fs.readFileSync(devstackJSONPath, 'utf-8');
    const dependentServices = JSON.parse(devstackJSONContent);

    const dependencyCommitsMapping = {};
    await Promise.all(
      Object.entries(dependentServices)
        .filter(([serviceName, commitId]) => shouldFetchProdCommitId(serviceName, commitId))
        .map(async ([serviceName]) => {
          const commitId = await fetchProdCommitId(serviceName);
          dependencyCommitsMapping[serviceName] = commitId;
          return { [serviceName]: commitId };
        }),
    );

    Object.entries(dependencyCommitsMapping).forEach(([serviceName, commitId]) => {
      dependentServices[serviceName] = commitId;
    });
    console.log('updated devstack.json:', JSON.stringify(dependentServices, null, 2));
    fs.writeFileSync(devstackJSONPath, JSON.stringify(dependentServices, null, 2));
    response = 'devstack.json updated with production commits';
  } catch (error) {
    console.error('Encountered an error:', error);
    response = 'Updating devstack.json with production commits failed';
    process.exitCode = 1;
  }
  return response;
}

(async () => {
  const response = await updateDevstackJSONWithProdCommits();
  console.log('response', response);
})();
