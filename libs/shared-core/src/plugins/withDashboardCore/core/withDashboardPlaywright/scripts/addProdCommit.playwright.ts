import fs from 'fs';
import {
  DEVSTACK_OVERRIDE_FILE_FOR_E2E_RUN,
  PLAYWRIGHT_SETUP_CACHE_DIR,
} from '../constants';
import { createFile } from '@src/scripts';

const prodCommitIdFetchURLMap: Record<string, string> = {
  api: 'https://api-web.dev.razorpay.in/commit.txt',
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
  splitz: 'https://splitz.dev.razorpay.in/commit.txt',
  subscriptions: 'https://subscriptions.razorpay.com/commit.txt',
  partnerships: 'https://partnerships-live.dev.razorpay.in/commit.txt',
  // razorx: 'https://razorx.dev.razorpay.in/commit.txt', // not updated with prod
};

const fetcher = async (url: string, method = 'GET') => {
  const response = await fetch(url, { method });

  const contentType = response.headers.get('content-type') || '';
  let data;
  if (contentType.includes('application/json')) {
    data = await response.json();
  } else if (contentType.includes('text/plain')) {
    data = await response.text();
  } else {
    // eslint-disable-next-line no-lonely-if
    if (response.ok) {
      throw new Error('Invalid response type');
    } else {
      // allow any content type for error responses (text is fallback parsing method)
      data = await response.text();
    }
  }

  if (!response.ok) {
    const error: any = new Error(`Error fetching commit.txt`);
    error.status = response.status;
    error.statusText = response.statusText;
    error.data = data;
    throw error;
  }

  return data;
};

const fetchProdCommitId = async (serviceName: string) => {
  let commitId = '';
  try {
    const commitUrl = prodCommitIdFetchURLMap[serviceName];
    const res = await fetcher(commitUrl);
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
  } catch (error: any) {
    console.log(`fetchProdCommitId:error - ${serviceName}`, error.message, 'Response:', error.data);
    console.log(`${serviceName} commit:`, 'failed. Using master commit.');
    // return empty string so that argo uses commit from master
    return '';
  }
  return commitId;
};

const shouldFetchProdCommitId = (serviceName: string, commitId: string) => {
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
    const devstackJSONContent = fs.readFileSync(DEVSTACK_OVERRIDE_FILE_FOR_E2E_RUN, 'utf-8');
    const dependentServices = JSON.parse(devstackJSONContent);

    const dependencyCommitsMapping: Record<string, string> = {};
    await Promise.all(
      Object.entries(dependentServices)
        .filter(([serviceName, commitId]) =>
          shouldFetchProdCommitId(serviceName, commitId as string),
        )
        .map(async ([serviceName]) => {
          const commitId = await fetchProdCommitId(serviceName);
          dependencyCommitsMapping[serviceName] = commitId;
          return { [serviceName]: commitId };
        }),
    );

    Object.entries(dependencyCommitsMapping).forEach(([serviceName, commitId]) => {
      dependentServices[serviceName] = commitId;
    });
    
    console.log(JSON.stringify(dependentServices, null, 2));

    createFile(PLAYWRIGHT_SETUP_CACHE_DIR, 'devstack.json', JSON.stringify(dependentServices, null, 2));

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
