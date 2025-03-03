const { execCommand } = require('./ci-utils');
const { writeFileSync, mkdtempSync, rmSync } = require('fs');
const os = require('os');
const path = require('path');

// TODO: Make this generic for supporting other workflows skip mechanisms
(async () => {
  try {
    const env = process.env;
    const targetBucket = env.target_bucket;
    const workflowName = env.workflow_name;
    const commitSha = env.commit_sha;
    const whySkip = env.why_skip;
    const doeApproval = env.doe_approval;

    const tempDir = mkdtempSync(path.join(os.tmpdir(), 'commit-'));
    const commitFilePath = path.join(tempDir, `${commitSha}.json`);

    const executionData = {
        targetBucket,
        workflowName,
        commitSha,
        whySkip,
        doeApproval,
      };

    writeFileSync(commitFilePath, JSON.stringify(executionData, null, 2), 'utf8');

    await execCommand(
      `aws s3 cp ${commitFilePath} s3://${targetBucket}/dashboard/ci-archive/dashboard-core/skipped-workflows/.playwright/${commitSha}.json --cache-control "public,max-age=31536000"`
    );

    rmSync(tempDir, { recursive: true, force: true });

    console.log(`Successfully marked ${commitSha} as skipped. Please re-run the workflow now to skip it.`);
  } catch (error) {
    console.error('Failed Executing Skip Trigger', error);
    process.exit(1);
  }
})();
