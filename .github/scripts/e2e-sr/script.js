/**
 * Main script for Dashboard E2E Success Rate reporting
 */

const fs = require('fs');
const { queryVictoriaMetrics, sendSlackMessage, formatTableForSlack } = require('./utils');

/**
 * Generate E2E success rate report by querying metrics
 * @returns {Object} Report data object
 */
async function generateReport() {
  // Define queries - Updated to match Grafana exactly
  const microFEsQuery = `sum(increase(label_replace(label_replace(lumberjack_merchant_dashboard_e2e_status{status="passed"}, "base_module", "newauth", "module", ".*/web/js/newauth/.*"), "base_module", "$1", "module", ".*/apps/([^/]+)/.*")[86400s])) by (base_module) / sum(increase(label_replace(label_replace(lumberjack_merchant_dashboard_e2e_status{}, "base_module", "newauth", "module", ".*/web/js/newauth/.*"), "base_module", "$1", "module", ".*/apps/([^/]+)/.*")[86400s])) by (base_module) * 100`;
  const webMicroFEsQuery = `(sum(increase(label_replace(lumberjack_merchant_dashboard_e2e_status{status="passed"}, "group", "$1", "module", "^/runner/_work/dashboard/dashboard/web/e2e/suites/([^/]+).*")[86400s])) by (group) / sum(increase(label_replace(lumberjack_merchant_dashboard_e2e_status{}, "group", "$1", "module", "^/runner/_work/dashboard/dashboard/web/e2e/suites/([^/]+).*")[86400s])) by (group)) * 100`;

  // Execute both queries with correct metric field names
  try {
    const microFEsResult = await queryVictoriaMetrics('Micro FEs', microFEsQuery, 'base_module');
    const webMicroFEsResult = await queryVictoriaMetrics(
      'Web FE Modules',
      webMicroFEsQuery,
      'group',
    );

    console.log('\n🎉 All queries completed successfully!');

    // Store results for Slack step
    const reportData = {
      microFEs: microFEsResult.tableData,
      webMicroFEs: webMicroFEsResult.tableData,
      timestamp: new Date().toISOString(),
    };

    // Set output for next step
    fs.writeFileSync('report-data.json', JSON.stringify(reportData, null, 2));

    return reportData;
  } catch (error) {
    console.error('One or more queries failed:', error.message);

    // Store error for Slack step
    const errorData = {
      error: error.message,
      timestamp: new Date().toISOString(),
    };

    fs.writeFileSync('report-data.json', JSON.stringify(errorData, null, 2));

    throw error;
  }
}

/**
 * Send the generated report to Slack
 * @param {string} slackToken - Slack bot token from environment
 */
async function sendReportToSlack(slackToken) {
  try {
    // Read report data from previous step
    let reportData;
    try {
      const reportJson = fs.readFileSync('report-data.json', 'utf8');
      reportData = JSON.parse(reportJson);
    } catch (error) {
      console.error('❌ Could not read report data:', error.message);
      await sendSlackMessage(
        '❌ *Dashboard E2E Report Failed*\nCould not read report data from previous step.',
        slackToken,
      );
      return;
    }

    // Check if there was an error in the previous step
    if (reportData.error) {
      await sendSlackMessage(
        `❌ *Dashboard E2E Report Failed*\nError: ${reportData.error}\nPlease check the GitHub Actions logs for more details.`,
        slackToken,
      );
      return;
    }

    // Build complete message with timestamp and title
    const now = new Date();
    const dateStr = now.toLocaleDateString('en-GB', {
      timeZone: 'Asia/Kolkata',
      day: '2-digit',
      month: 'long',
      year: 'numeric',
    });
    const timeStr = now.toLocaleTimeString('en-US', {
      timeZone: 'Asia/Kolkata',
      hour: 'numeric',
      minute: '2-digit',
      hour12: true,
    });
    const timestamp = `${dateStr} (${timeStr})`;

    let completeMessage = `🚀 *Dashboard E2E Success Rate Report*\n📅 ${timestamp} IST\n\n<!subteam^S0177NKFJTF> Daily automated testing results for all dashboard modules:\n\n`;

    // Add Micro FEs report
    if (reportData.microFEs) {
      completeMessage += formatTableForSlack(reportData.microFEs, 'Micro FEs');
    }

    // Add Web Micro FEs report
    if (reportData.webMicroFEs) {
      completeMessage += formatTableForSlack(reportData.webMicroFEs, 'Web FE Modules');
    }

    // Send the complete message
    const success = await sendSlackMessage(completeMessage, slackToken);

    if (success) {
      console.log('🎉 Complete report sent to Slack successfully!');
    } else {
      console.error('❌ Failed to send complete report to Slack');
    }
  } catch (error) {
    console.error('❌ Error in Slack reporting step:', error.message);
    await sendSlackMessage(
      `❌ *Dashboard E2E Slack Report Failed*\nError: ${error.message}`,
      slackToken,
    );
  }
}

module.exports = {
  generateReport,
  sendReportToSlack,
};
