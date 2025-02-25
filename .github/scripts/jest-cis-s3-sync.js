const ENV = process.env;
const { execCommand } = require('./ci-utils');
const { existsSync } = require('fs');

(async () => {
  try {
    const targetBucket = process.argv.slice(2)[0].trim();

    const targetProjects = process.argv.slice(2)[1].trim().split(',');
    const targetProjectRoots = process.argv.slice(2)[2].trim().split(',');

    if (targetProjects.length !== targetProjectRoots.length) {
      throw new Error('Project and Project Root Mismatch. Please check again.');
    }

    if (!Boolean(targetBucket)) {
      throw new Error('Bucket is not specified. Please check again.');
    }

    const promises = targetProjectRoots.map((rootDir, index) => {
      if (existsSync(`${rootDir}/.jest-analysis`)) {
        // [Note]Not in use for now. To be used after based on requirement
        if (ENV.SHARD) {
          return execCommand(
            `aws s3 cp --recursive --metadata-directive REPLACE --cache-control "max-age=0,no-cache,no-store,must-revalidate" ${rootDir}/.jest-analysis s3://${targetBucket}/dashboard/code-integrity-suite/${ENV.COMMIT_SHA}/${targetProjects[index]}/.jest-cache/jest-analysis-${ENV.SHARD}`,
          );
        }

        return execCommand(
          `aws s3 cp --recursive --metadata-directive REPLACE --cache-control "max-age=0,no-cache,no-store,must-revalidate" ${rootDir}/.jest-analysis s3://${targetBucket}/dashboard/code-integrity-suite/${ENV.COMMIT_SHA}/${targetProjects[index]}/jest-analysis`,
        );
      } else {
        console.warn(`\n.jest-analysis not found, skipping sync for ${targetProjects[index]}`);
        return false;
      }
    });

    await Promise.all(promises);

    console.log(`Successfully Synced Jest Analysis Artifacts to S3.`);
  } catch (error) {
    console.error(`Error Syncing Browser Artifacts to S3.`, error);
    process.exit(1);
  }
})();
