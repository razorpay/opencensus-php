const fs = require('fs');

const runAttempt = parseInt(process.env.GITHUB_RUN_ATTEMPT || '1', 10);

let summaryContent =
  runAttempt === 1
    ? `## ✨ Playwright Analysis Reports\n\n`
    : `## ✨ Playwright Analysis Reports - Retry ${runAttempt}\n\n`;

const generatePlaywrightReports = ({ targetProjects, sha }) => {
  summaryContent += `| Project Name       | Report Link                             |\n`;
  summaryContent += `|--------------------|-----------------------------------------|\n`;

  targetProjects.forEach((project) => {
    const reportUrl = `https://dashboard-assets.np.razorpay.in/dashboard/code-integrity-suite/${sha}/${project}/playwright-analysis/${runAttempt}/analysis/index.html`;
    summaryContent += `| ${project.padEnd(18)} | [View Report](${reportUrl}) |\n`;
  });
};

const generateSummary = ({ playwrightConfiguredAffectedApps = [], sha }) => {
  const targetProjects = ['newauth-dashboard', ...playwrightConfiguredAffectedApps];

  generatePlaywrightReports({ targetProjects, sha });

  summaryContent += `\n\n`;

  const summaryFilePath = process.env.GITHUB_STEP_SUMMARY;
  if (!summaryFilePath) {
    console.error('GITHUB_STEP_SUMMARY environment variable not set.');
    process.exit(1);
  }

  fs.writeFileSync(summaryFilePath, summaryContent);
  console.log('Summary generated successfully.');
};

const inputs = {
  playwrightConfiguredAffectedApps: JSON.parse(process.env.PLAYWRIGHT_CONFIGURED_AFFECTED_APPS),
  sha: process.env.GITHUB_SHA,
};

generateSummary(inputs);
