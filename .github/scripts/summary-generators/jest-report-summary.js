const fs = require('fs');

let summaryContent = `## ✨ Jest Analysis Reports\n\n`;

const generateDefaultAppsSummary = ({ defaultApps, sha }) => {
  summaryContent += `| Project Name       | Report Link                             |\n`;
  summaryContent += `|--------------------|-----------------------------------------|\n`;

  const combinedApps = [...defaultApps.flatMap((entry) => entry.split(','))]
    .map((app) => app.trim())
    .filter(Boolean);

  combinedApps.forEach((project) => {
    const reportUrl = `https://dashboard-assets.np.razorpay.in/dashboard/code-integrity-suite/${sha}/${project}/jest-analysis/html-report/index.html`;
    summaryContent += `| ${project.padEnd(18)} | [View Report](${reportUrl}) |\n`;
  });
};

const generateIntensiveAppsSummary = ({ memoryIntensiveApps, sha, totalShards }) => {
  const allLabels = memoryIntensiveApps.flatMap((project) => {
    const shardLabels = Array.from({ length: totalShards }, (_, i) => `Shard - ${i + 1}`);
    return [`${project}`, ...shardLabels];
  });
  const columnWidth = Math.max(...allLabels.map((label) => label.length)) + 2;
  const shardsArray = Array.from({ length: totalShards }, (_, i) => i + 1);

  memoryIntensiveApps.forEach((project) => {
    const header = `${project}`;
    const supportingText = `Report Link`;

    summaryContent += `| ${header.padEnd(columnWidth)} | ${supportingText}${' '.repeat(
      columnWidth - supportingText.length,
    )} |\n`;

    summaryContent += `|${'-'.repeat(columnWidth)}--|${'-'.repeat(columnWidth)}--|\n`;

    // Attach Reports for each shard
    shardsArray.forEach((shardIndex) => {
      const shardLabel = `Report - ${shardIndex}`;
      const reportUrl = `https://dashboard-assets.np.razorpay.in/dashboard/code-integrity-suite/${sha}/${project}/.jest-cache/jest-analysis-${shardIndex}/html-report/index.html`;
      summaryContent += `| ${shardLabel.padEnd(
        columnWidth,
      )} | ${`[View Report](${reportUrl})`.padEnd(columnWidth)} |\n`;
    });

    // Attach Combined Coverage
    const combinedCoverage = `Combined Coverage`;
    const combinedCoverageUrl = `https://dashboard-assets.np.razorpay.in/dashboard/code-integrity-suite/${sha}/${project}/jest-analysis/lcov-report/index.html`;
    summaryContent += `| ${combinedCoverage.padEnd(
      columnWidth,
    )} | ${`[View Coverage](${combinedCoverageUrl})`.padEnd(columnWidth)} |\n`;

    summaryContent += `\n`;
  });
};

const generateSummary = ({ defaultApps, memoryIntensiveApps, sha, totalShards }) => {
  generateDefaultAppsSummary({ defaultApps, sha });

  summaryContent += `\n`;

  generateIntensiveAppsSummary({ memoryIntensiveApps, sha, totalShards });

  summaryContent += `\n\n`;
  summaryContent += `> <sup>**Each shard's link will be available only after completion of its workflow job. Kindly ignore the coverage report at individual shard level, focus on combined coverage which will be available post completion of CIS suite.**</sup><br/>`;
  summaryContent += `<sup>*(If any job fails, reports will not be available, in that case please check that shard's job step.)*</sup>\n`;

  const summaryFilePath = process.env.GITHUB_STEP_SUMMARY;
  if (!summaryFilePath) {
    console.error('GITHUB_STEP_SUMMARY environment variable not set.');
    process.exit(1);
  }

  fs.writeFileSync(summaryFilePath, summaryContent);
  console.log('Summary generated successfully.');
};

const inputs = {
  defaultApps: JSON.parse(process.env.DEFAULT_APPS_MATRIX),
  memoryIntensiveApps: JSON.parse(process.env.MEMORY_INTENSIVE_APPS),
  totalShards: JSON.parse(process.env.SHARDS_TOTAL),
  sha: process.env.GITHUB_SHA,
};

generateSummary(inputs);
