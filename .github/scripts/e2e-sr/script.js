/**
 * Main script for Dashboard E2E Success Rate reporting
 */

const fs = require('fs');
const { queryVictoriaMetrics, sendSlackMessage, formatTableForSlack } = require('./utils');
const { getTimeRangeConfig, getTimeRangeDisplay, formatTimestamp } = require('./timestamps');

/**
 * Generate E2E success rate report by querying metrics
 * @param {Object} timestamps - Optional timestamps object (if not provided, will calculate new ones)
 * @returns {Object} Report data object
 */
async function generateReport(timestamps) {
  // Calculate timestamps if not provided
  if (!timestamps) {
    // Check for custom timestamps from environment variables
    const customFrom = process.env.E2E_CUSTOM_FROM ? parseInt(process.env.E2E_CUSTOM_FROM) : null;
    const customTo = process.env.E2E_CUSTOM_TO ? parseInt(process.env.E2E_CUSTOM_TO) : null;

    timestamps = getTimeRangeConfig(customFrom, customTo);
  }

  console.log('🕐 Using timestamps:', timestamps);
  console.log(getTimeRangeDisplay(timestamps));
  // Define queries for Micro FEs with dynamic time range
  const timeRange = `[${timestamps.durationSeconds}s]`;
  const microFEsSuccessRateQuery = `sum(increase(label_replace(label_replace(label_replace(lumberjack_merchant_dashboard_e2e_status{status="passed"}, "base_module", "newauth", "module", ".*/web/js/newauth/.*"), "base_module", "web", "module", ".*/web/e2e/suites/.*"), "base_module", "$1", "module", ".*/apps/([^/]+)/.*")${timeRange})) by (base_module) / sum(increase(label_replace(label_replace(label_replace(lumberjack_merchant_dashboard_e2e_status{}, "base_module", "newauth", "module", ".*/web/js/newauth/.*"), "base_module", "web", "module", ".*/web/e2e/suites/.*"), "base_module", "$1", "module", ".*/apps/([^/]+)/.*")${timeRange})) by (base_module) * 100`;

  const microFEsTotalRunsTemplate = `sum(increase(lumberjack_merchant_dashboard_e2e_status{module=~".*\${module_name}.*"}${timeRange}))`;

  const microFEsFailedRunsTemplate = `sum(increase(lumberjack_merchant_dashboard_e2e_status{module=~".*\${module_name}.*", status="failed"}${timeRange}))`;

  const microFEsUniqueFailuresTemplate = `sum(increase(lumberjack_merchant_dashboard_e2e_status{module=~".*\${module_name}.*", status="failed"}${timeRange})) by (title,status)`;

  // Define queries for Web E2Es with dynamic time range
  const webMicroFEsSuccessRateQuery = `(sum(increase(label_replace(lumberjack_merchant_dashboard_e2e_status{status="passed", module!~".*/apps/[^/]+/.*|.*/web/js/newauth/.*"}, "group", "$1", "module", "^/runner/_work/dashboard/dashboard/web/e2e/suites/([^/]+).*")${timeRange})) by (group) / sum(increase(label_replace(lumberjack_merchant_dashboard_e2e_status{module!~".*/apps/[^/]+/.*|.*/web/js/newauth/.*"}, "group", "$1", "module", "^/runner/_work/dashboard/dashboard/web/e2e/suites/([^/]+).*")${timeRange})) by (group)) * 100`;

  const webMicroFEsTotalRunsTemplate = `sum(increase(lumberjack_merchant_dashboard_e2e_status{module=~".*\${web_module_name}.*"}${timeRange}))`;

  const webMicroFEsFailedRunsTemplate = `sum(increase(lumberjack_merchant_dashboard_e2e_status{module=~".*\${web_module_name}.*", status="failed"}${timeRange}))`;

  const webMicroFEsUniqueFailuresTemplate = `sum(increase(lumberjack_merchant_dashboard_e2e_status{module=~".*\${web_module_name}.*", status="failed"}${timeRange})) by (title,status)`;

  // Execute both queries with individual module processing
  try {
    const microFEsResult = await queryVictoriaMetrics(
      'Micro FEs',
      microFEsSuccessRateQuery,
      microFEsTotalRunsTemplate,
      microFEsFailedRunsTemplate,
      microFEsUniqueFailuresTemplate,
      'base_module',
      'module_name',
      timestamps,
    );

    const webMicroFEsResult = await queryVictoriaMetrics(
      'Web FE Modules',
      webMicroFEsSuccessRateQuery,
      webMicroFEsTotalRunsTemplate,
      webMicroFEsFailedRunsTemplate,
      webMicroFEsUniqueFailuresTemplate,
      'group',
      'web_module_name',
      timestamps,
    );

    console.log('\n🎉 All queries completed successfully!');

    // Store results for Slack step
    const reportData = {
      microFEs: microFEsResult.tableData,
      webMicroFEs: webMicroFEsResult.tableData,
      timestamp: new Date().toISOString(),
      timestamps: timestamps, // Include timestamps for Slack step
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
      timestamps: timestamps, // Include timestamps for Slack step
    };

    fs.writeFileSync('report-data.json', JSON.stringify(errorData, null, 2));

    throw error;
  }
}

/**
 * Send the generated report to Slack
 * @param {string} slackToken - Slack bot token from environment
 * @param {Object} timestamps - Optional timestamps object passed from workflow
 */
async function sendReportToSlack(slackToken, timestamps) {
  // Check if we're in test mode via environment variable
  const isTestMode = process.env.E2E_SLACK_TEST_MODE === 'true';

  if (isTestMode) {
    console.log('🧪 Running in TEST MODE - will use test channel and remove ALL mentions');
  } else {
    console.log(
      '🚀 Running in PRODUCTION MODE - will use production channel with conditional mentions',
    );

    // Log mention configuration
    const tagDirectors = process.env.E2E_TAG_DIRECTORS === 'true';
    const tagEMs = process.env.E2E_TAG_EMS === 'true';

    console.log(`   📢 Team mention: ENABLED`);
    console.log(`   👔 Directors mention: ${tagDirectors ? 'ENABLED' : 'DISABLED'}`);
    console.log(`   🎯 EMs mention: ${tagEMs ? 'ENABLED' : 'DISABLED'}`);
  }

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

    // Use timestamps from report data if available, otherwise use passed timestamps
    const reportTimestamps = reportData.timestamps || timestamps;

    // Build complete message with timestamp from data range end time
    const timestamp = formatTimestamp(reportTimestamps.to);
    const grafanaLink = reportTimestamps?.grafanaLink
      ? `\n🔗 *<${reportTimestamps.grafanaLink}|View Grafana Dashboard>*`
      : '';

    // Build mentions based on test mode and conditional flags
    let mentions = '';

    if (!isTestMode) {
      // In production mode, show mentions
      mentions += '<!subteam^S0177NKFJTF> '; // Always include team mention

      // Check for director tagging
      const tagDirectors = process.env.E2E_TAG_DIRECTORS === 'true';
      if (tagDirectors) {
        mentions += '<!subteam^S01S8HD3Q3U> ';
      }

      // Check for EM tagging
      const tagEMs = process.env.E2E_TAG_EMS === 'true';
      if (tagEMs) {
        mentions += '<!subteam^S050BKLJ7GX> ';
      }
    }
    // In test mode, mentions remains empty string

    let completeMessage = `🚀 *Dashboard E2E Success Rate Report*\n📅 ${timestamp}${grafanaLink}\n\n${mentions}Daily automated testing results for all dashboard modules:\n\n`;

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
