const ENV = process.env;
const { execCommand } = require('./ci-utils');
const { existsSync, writeFileSync } = require('fs');

const legacyDirMap = {
  'payments-dashboard': 'merchant',
  'la-dashboard': 'merchantLA',
  'pokedex-dashboard': 'pokedex',
  'newauth-dashboard': 'newAuth',
  'tnc-dashboard': 'merchantTnc',
  'razorx-dashboard': 'razorx',
};

(async () => {
  try {
    let buildDir = process.argv.slice(2)[0].trim();
    const targetProject = process.argv.slice(2)[1].trim();
    const targetBucket = process.argv.slice(2)[2].trim();

    if (Boolean(legacyDirMap[targetProject])) {
      buildDir = buildDir.replace(targetProject, legacyDirMap[targetProject]);
    }

    if (!existsSync(buildDir)) {
      throw new Error("Dir doesn't exist. Please check again.");
    }

    if (!Boolean(targetProject)) {
      throw new Error('Project not specified. Please check again.');
    }

    if (!Boolean(targetBucket)) {
      throw new Error('Bucket is not specified. Please check again.');
    }

    const nonCachedFiles = [
      'commit.txt',
      `${targetProject}.entry.js`,
      `${targetProject}.preload.json`,
      `${targetProject}.entry.js.gz`,
      `${targetProject.split('-').join('_')}.loadable-stats.json`,
      `${targetProject.split('-').join('_')}.remoteEntry.js`,
      `${targetProject.split('-').join('_')}.remoteEntry.js.gz`,
    ];

    let excludedFilesFromFinalSync = '';

    console.log(`\nSyncing Browser Artifacts to S3 for ${targetProject}... Please wait...`);

    // Add version of the app (available at <baseUrl>/dashboard/core-bundles/<app-name>/commit.txt)
    writeFileSync(`${buildDir}/commit.txt`, ENV.VERSION, 'utf8');

    console.log(`\nGenerated app version (commit.txt) for ${targetProject}.`);

    const nonCachedFilesSyncPromises = nonCachedFiles.map((targetFile) => {
      const targetFileFullPath = `${buildDir}/${targetFile}`;
      if (existsSync(targetFileFullPath)) {
        excludedFilesFromFinalSync += ` --exclude ${targetFileFullPath}`;
        return execCommand(
          `aws s3 cp --metadata-directive REPLACE --cache-control "max-age=0,no-cache,no-store,must-revalidate" ${targetFileFullPath} s3://${targetBucket}/dashboard/core-bundles/${ENV.ENVIRONMENT}/${ENV.VERSION}/${targetProject}/${targetFile}`,
        );
      } else {
        console.warn(`\nFile not found, skipping ${targetFile}`);
      }
    });

    await Promise.all(nonCachedFilesSyncPromises);

    await execCommand(
      `aws s3 sync --cache-control "public,max-age=31536000" ${buildDir} s3://${targetBucket}/dashboard/core-bundles/${ENV.ENVIRONMENT}/${ENV.VERSION}/${targetProject}${excludedFilesFromFinalSync}`,
    );

    console.log(`Successfully Browser Artifacts to S3 for ${targetProject}.`);
  } catch (error) {
    console.error(`Error Syncing Browser Artifacts to S3.`, error);
    process.exit(1);
  }
})();
