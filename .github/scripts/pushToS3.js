const ENV = process.env;
const { exec } = require('child_process');

// Add list of micro-apps to build with build path map
const PROJECT_BUILD_NAME_MAP = {
  'self-serve': 'selfserve',
  pos: 'pos',
};

const pushRemoteAppsToS3 = () => {
  const affectedProjects = process.argv.slice(2)[0].trim();
  const targetBucket = process.argv.slice(2)[1].trim();

  const remoteApps = affectedProjects
    .split(',')
    .filter((project) => PROJECT_BUILD_NAME_MAP[project]);

  let awsCliCommand = '';

  if (remoteApps.length === 0) return;

  remoteApps.forEach(async (project) => {
    const buildName = PROJECT_BUILD_NAME_MAP[project];

    awsCliCommand = awsCliCommand.concat(
      '\n',
      `aws s3 cp \
    --metadata-directive REPLACE \
    --cache-control "max-age=0,no-cache,no-store,must-revalidate" \
    apps/${project}/${ENV.BUILD_PATH}/${buildName}.remoteEntry.js s3://${targetBucket}/dashboard/federated-bundles/${buildName}/${ENV.ENVIRONMENT}/${ENV.VERSION}/${ENV.BUILD_PATH}/${buildName}.remoteEntry.js

    aws s3 sync --cache-control "public,max-age=31536000" apps/${project}/${ENV.BUILD_PATH} s3://${targetBucket}/dashboard/federated-bundles/${buildName}/${ENV.ENVIRONMENT}/${ENV.VERSION}/${ENV.BUILD_PATH} --exclude ${ENV.BUILD_PATH}/${buildName}.remoteEntry.js
    `,
    );
  });

  console.log(`Syncing to S3...`, awsCliCommand);

  exec(awsCliCommand, (error) => {
    if (error) {
      console.error(`Error executing command: ${error.message}`);
      return;
    }
  });
};

pushRemoteAppsToS3();
