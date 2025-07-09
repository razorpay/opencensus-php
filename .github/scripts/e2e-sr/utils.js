/**
 * Utility functions for Dashboard E2E Success Rate reporting
 */

/**
 * Get color code based on success percentage
 * @param {number} percentage - Success rate percentage
 * @returns {string} Color emoji
 */
function getColorCode(percentage) {
  if (percentage > 90) return '🟢'; // Green (>90)
  if (percentage >= 50) return '🟠'; // Amber (50-90)
  return '🔴'; // Red (<50)
}

/**
 * Parse API response data into a formatted table structure
 * @param {Object} response - API response object
 * @param {string} queryName - Name of the query for logging
 * @param {string} metricFieldName - Field name to extract from metrics (default: 'group')
 * @returns {Array} Array of formatted table data
 */
function parseResponseToTable(response, queryName, metricFieldName = 'group') {
  if (!response.data || !response.data.result) {
    console.log(`❌ No data found for ${queryName}`);
    return [];
  }

  const tableData = response.data.result.map((item) => {
    const group = item.metric[metricFieldName] || 'Unknown';
    const percentage = parseFloat(item.value[1]);
    const colorCode = getColorCode(percentage);

    return {
      group: group,
      successRate: percentage.toFixed(2),
      color: colorCode,
      status: `${colorCode} ${percentage.toFixed(2)}%`,
      // Initialize additional metrics - will be populated later
      totalRuns: 0,
      failedRuns: 0,
      uniqueFailures: 0,
    };
  });

  // Sort by success rate (ascending)
  tableData.sort((a, b) => parseFloat(a.successRate) - parseFloat(b.successRate));

  return tableData;
}

/**
 * Display formatted table in console with additional metrics
 * @param {Array} tableData - Array of table row data
 * @param {string} queryName - Name of the query for display
 */
function displayTable(tableData, queryName) {
  console.log(`\n📊 ${queryName} - Success Rate Table:`);

  // Calculate optimal column widths based on data
  const widths = calculateColumnWidths(tableData);
  const totalWidth =
    widths.module + widths.rate + widths.status + widths.failedTotal + widths.unique + 5;

  console.log('═'.repeat(totalWidth));
  console.log(getTableHeader(widths));
  console.log('─'.repeat(totalWidth));

  // Use shared row formatter with dynamic widths
  tableData.forEach((row) => {
    console.log(formatTableRow(row, widths));
  });

  console.log('═'.repeat(totalWidth));
  console.log(`Total Modules: ${tableData.length}`);

  // Use shared summary calculator
  const summary = getColorSummary(tableData);
  console.log(`🟢 Green (>90%): ${summary.green}`);
  console.log(`🟠 Amber (50-90%): ${summary.amber}`);
  console.log(`🔴 Red (<50%): ${summary.red}`);
}

/**
 * Query Victoria Metrics API with the given parameters (single query)
 * @param {string} queryName - Name of the query for logging
 * @param {string} queryData - Query string to execute
 * @param {string} metricFieldName - Field name to extract from metrics (default: 'group')
 * @param {Object} timestamps - Time range configuration object
 * @returns {Object} Object containing response, tableData, and queryName
 */
async function queryVictoriaMetricsSingle(
  queryName,
  queryData,
  metricFieldName = 'group',
  timestamps = null,
) {
  const apiUrl =
    'https://victoriametrics-cold-ops.razorpay.com/select/447581938/prometheus/api/v1/query';

  const formData = new URLSearchParams();
  formData.append('query', queryData);
  formData.append('step', '2m0s'); // Match Grafana step parameter exactly

  // Add time range parameters if timestamps are provided
  if (timestamps) {
    formData.append('time', timestamps.to.toString()); // Query time (end time)
    formData.append('start', timestamps.from.toString()); // Start time for range queries
  }

  const requestOptions = {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      Accept: 'application/json',
    },
    body: formData.toString(),
  };

  try {
    console.log(`\n🚀 Executing ${queryName}...`);
    const response = await fetch(apiUrl, requestOptions);

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const jsonResponse = await response.json();

    console.log(`✅ ${queryName} - Status:`, response.status);

    return { response: jsonResponse, queryName };
  } catch (error) {
    console.error(`❌ ${queryName} failed:`, error.message);
    throw error;
  }
}

/**
 * Query Victoria Metrics with multiple queries to get comprehensive data
 * @param {string} queryName - Name of the query for logging
 * @param {string} successRateQuery - Success rate query string
 * @param {string} totalRunsQueryTemplate - Total runs query template with ${module_name} placeholder
 * @param {string} failedRunsQueryTemplate - Failed runs query template with ${module_name} placeholder
 * @param {string} uniqueFailuresQueryTemplate - Unique failures query template with ${module_name} placeholder
 * @param {string} metricFieldName - Field name to extract from metrics
 * @param {string} moduleVariableName - Variable name to substitute in templates (module_name or web_module_name)
 * @param {Object} timestamps - Time range configuration object
 * @returns {Object} Object containing enhanced table data
 */
async function queryVictoriaMetrics(
  queryName,
  successRateQuery,
  totalRunsQueryTemplate,
  failedRunsQueryTemplate,
  uniqueFailuresQueryTemplate,
  metricFieldName = 'group',
  moduleVariableName = 'module_name',
  timestamps = null,
) {
  try {
    console.log(`\n🚀 Executing ${queryName} with individual module queries...`);

    // First, get the success rate data to determine which modules exist
    const successRateResult = await queryVictoriaMetricsSingle(
      `${queryName} - Success Rate`,
      successRateQuery,
      metricFieldName,
      timestamps,
    );

    // Parse success rate data (base table structure)
    const tableData = parseResponseToTable(successRateResult.response, queryName, metricFieldName);

    if (tableData.length === 0) {
      console.log(`❌ No modules found for ${queryName}`);
      return { tableData, queryName };
    }

    console.log(
      `📋 Found ${tableData.length} modules for ${queryName}, running individual queries...`,
    );

    // For each module in our success rate data, run individual queries
    const modulePromises = tableData.map(async (row) => {
      const moduleKey = row.group;
      console.log(`  🔍 Processing module: ${moduleKey}`);

      // Substitute module name in query templates
      const totalRunsQuery = totalRunsQueryTemplate.replace(`\${${moduleVariableName}}`, moduleKey);
      const failedRunsQuery = failedRunsQueryTemplate.replace(
        `\${${moduleVariableName}}`,
        moduleKey,
      );
      const uniqueFailuresQuery = uniqueFailuresQueryTemplate.replace(
        `\${${moduleVariableName}}`,
        moduleKey,
      );

      // Execute queries for this specific module
      try {
        const [totalResult, failedResult, uniqueResult] = await Promise.all([
          queryVictoriaMetricsSingle(
            `${moduleKey} - Total`,
            totalRunsQuery,
            metricFieldName,
            timestamps,
          ),
          queryVictoriaMetricsSingle(
            `${moduleKey} - Failed`,
            failedRunsQuery,
            metricFieldName,
            timestamps,
          ),
          queryVictoriaMetricsSingle(
            `${moduleKey} - Unique`,
            uniqueFailuresQuery,
            metricFieldName,
            timestamps,
          ),
        ]);

        // Parse results
        const totalRuns = totalResult.response.data?.result?.[0]?.value?.[1]
          ? parseInt(totalResult.response.data.result[0].value[1])
          : 0;
        const failedRuns = failedResult.response.data?.result?.[0]?.value?.[1]
          ? parseInt(failedResult.response.data.result[0].value[1])
          : 0;

        // Filter out entries with value="0" as they didn't actually fail during the time period
        const uniqueFailureAllResults = uniqueResult.response.data?.result || [];
        const uniqueFailuresWithActualFailures = uniqueFailureAllResults.filter((item) => {
          const condition = parseInt(item.value[1]) > 0;
          if (!condition) {
            console.log(`    📋 ${moduleKey}: Filtered out ${item.value[1]}`);
          }
          return condition;
        });
        const uniqueFailures = uniqueFailuresWithActualFailures.length;
        const filteredOutCount = uniqueFailureAllResults.length - uniqueFailures;

        console.log(
          `    ✅ ${moduleKey}: Total=${totalRuns}, Failed=${failedRuns}, Unique=${uniqueFailures}`,
        );

        if (filteredOutCount > 0) {
          console.log(`    📋 ${moduleKey}: Filtered out ${filteredOutCount} entries with value=0`);
        }

        return {
          moduleName: moduleKey,
          totalRuns,
          failedRuns,
          uniqueFailures,
        };
      } catch (error) {
        console.error(`    ❌ Failed to get metrics for module ${moduleKey}:`, error.message);
        return {
          moduleName: moduleKey,
          totalRuns: 0,
          failedRuns: 0,
          uniqueFailures: 0,
        };
      }
    });

    // Wait for all module queries to complete
    const moduleResults = await Promise.all(modulePromises);

    // Enhance table data with the results
    tableData.forEach((row) => {
      const moduleResult = moduleResults.find((r) => r.moduleName === row.group);
      if (moduleResult) {
        row.totalRuns = moduleResult.totalRuns;
        row.failedRuns = moduleResult.failedRuns;
        row.uniqueFailures = moduleResult.uniqueFailures;
      } else {
        row.totalRuns = 0;
        row.failedRuns = 0;
        row.uniqueFailures = 0;
      }
    });

    // Display enhanced table
    displayTable(tableData, queryName);

    console.log(`✅ ${queryName} completed with ${tableData.length} modules processed`);

    return { tableData, queryName };
  } catch (error) {
    console.error(`❌ ${queryName} failed:`, error.message);
    throw error;
  }
}

/**
 * Send a message to Slack channel
 * Test mode can be configured in YML file by setting: E2E_SLACK_TEST_MODE: 'true'
 * @param {string} message - Message to send
 * @param {string} slackToken - Slack bot token
 * @returns {boolean} Success status
 */
async function sendSlackMessage(message, slackToken) {
  const isTestMode = process.env.E2E_SLACK_TEST_MODE === 'true';
  // Production channel: #payments-dashboard
  // Test channel: #testing-automation
  const productionChannelId = 'C0156ULAEFQ';
  const testChannelId = 'C05001MF81K';

  const channelId = isTestMode ? testChannelId : productionChannelId;

  if (!slackToken) {
    console.error('❌ DASHBOARD_E2E_SLACK_BOT_TOKEN environment variable is not set');
    return false;
  }

  // Remove mentions in test mode
  let processedMessage = message;
  if (isTestMode) {
    // Remove team mentions like <!subteam^S0177NKFJTF>
    processedMessage = message.replace(/<!subteam\^[^>]+>/g, '');
    console.log(`📧 Sending to TEST channel: ${channelId}`);
  } else {
    console.log(`📧 Sending to PRODUCTION channel: ${channelId}`);
  }

  const slackPayload = {
    channel: channelId,
    text: processedMessage,
  };

  try {
    const response = await fetch('https://slack.com/api/chat.postMessage', {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${slackToken}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(slackPayload),
    });

    const result = await response.json();

    if (result.ok) {
      console.log('✅ Message sent to Slack successfully');
      return true;
    } else {
      console.error('❌ Failed to send Slack message:', result.error);
      return false;
    }
  } catch (error) {
    console.error('❌ Error sending Slack message:', error.message);
    return false;
  }
}

/**
 * Get color summary statistics (shared helper)
 * @param {Array} tableData - Array of table row data
 * @returns {Object} Summary object with green, amber, red counts
 */
function getColorSummary(tableData) {
  return {
    green: tableData.filter((r) => r.color === '🟢').length,
    amber: tableData.filter((r) => r.color === '🟠').length,
    red: tableData.filter((r) => r.color === '🔴').length,
  };
}

/**
 * Calculate optimal column widths based on data
 * @param {Array} tableData - Array of table row data
 * @returns {Object} Object with column widths
 */
function calculateColumnWidths(tableData) {
  // Set minimum widths to accommodate header text + padding
  const minWidths = {
    module: 20,
    rate: 14,
    status: 8,
    failedTotal: 25,
    unique: 17,
  };

  let maxWidths = { ...minWidths };

  tableData.forEach((row) => {
    // Check module name width
    const moduleLength = row.group.length;
    if (moduleLength > maxWidths.module) {
      console.log(
        `📏 Module column: ${maxWidths.module} → ${moduleLength + 2} (Module name "${
          row.group
        }" - ${moduleLength} chars)`,
      );
      maxWidths.module = moduleLength;
    }

    // Check success rate width
    const rateLength = `${row.successRate}%`.length;
    if (rateLength > maxWidths.rate) {
      console.log(
        `📏 Rate column: ${maxWidths.rate} → ${rateLength + 2} (Success rate "${
          row.successRate
        }%" - ${rateLength} chars)`,
      );
      maxWidths.rate = rateLength;
    }

    // Check failed/total runs width
    const failedTotalLength = `${row.failedRuns}/${row.totalRuns}`.length;
    if (failedTotalLength > maxWidths.failedTotal) {
      console.log(
        `📏 Failed/Total column: ${maxWidths.failedTotal} → ${
          failedTotalLength + 2
        } (Failed/Total "${row.failedRuns}/${row.totalRuns}" - ${failedTotalLength} chars)`,
      );
      maxWidths.failedTotal = failedTotalLength;
    }

    // Check unique failures width
    const uniqueLength = row.uniqueFailures.toString().length;
    if (uniqueLength > maxWidths.unique) {
      console.log(
        `📏 Unique column: ${maxWidths.unique} → ${uniqueLength + 2} (Unique failures "${
          row.uniqueFailures
        }" - ${uniqueLength} chars)`,
      );
      maxWidths.unique = uniqueLength;
    }
  });

  // Add some padding
  maxWidths.module += 2;
  maxWidths.rate += 2;
  maxWidths.failedTotal += 2;
  maxWidths.unique += 2;

  return maxWidths;
}

/**
 * Get table header (shared helper)
 * @param {Object} widths - Column widths object
 * @returns {string} Formatted header string
 */
function getTableHeader(widths) {
  return (
    'Module'.padEnd(widths.module) +
    'Success Rate'.padEnd(widths.rate) +
    'Status'.padEnd(widths.status) +
    'Failed runs/Total runs'.padEnd(widths.failedTotal) +
    'Unique Failures'
  );
}

/**
 * Format a single table row (shared helper)
 * @param {Object} row - Table row data
 * @param {Object} widths - Column widths object
 * @returns {string} Formatted row string
 */
function formatTableRow(row, widths) {
  const moduleName = row.group.padEnd(widths.module);
  const rate = `${row.successRate}%`.padEnd(widths.rate);
  const status = row.color.padEnd(widths.status);
  const failedTotal = `${row.failedRuns}/${row.totalRuns}`.padEnd(widths.failedTotal);
  const uniqueFailures = row.uniqueFailures.toString();

  return `${moduleName}${rate}${status}${failedTotal}${uniqueFailures}`;
}

/**
 * Format table data for Slack display
 * @param {Array} tableData - Array of table row data
 * @param {string} queryName - Name of the query for display
 * @returns {string} Formatted Slack message
 */
function formatTableForSlack(tableData, queryName) {
  if (!tableData || tableData.length === 0) {
    return `:bar_chart: *${queryName}* - Summary: 🟢 0 | 🟠 0 | 🔴 0\n❌ No data available\n\n`;
  }

  const summary = getColorSummary(tableData);

  let slackMessage = `:bar_chart: *${queryName}* - Summary: 🟢 ${summary.green} | 🟠 ${summary.amber} | 🔴 ${summary.red}\n`;

  // Calculate optimal column widths for consistent formatting
  const widths = calculateColumnWidths(tableData);
  const totalWidth =
    widths.module + widths.rate + widths.status + widths.failedTotal + widths.unique + 5;

  // Add table header
  slackMessage += '```\n';
  slackMessage += getTableHeader(widths) + '\n';
  slackMessage += '─'.repeat(totalWidth) + '\n';

  // Add table rows using shared formatter with dynamic widths
  tableData.forEach((row) => {
    slackMessage += formatTableRow(row, widths) + '\n';
  });

  slackMessage += '```\n\n';

  return slackMessage;
}

module.exports = {
  getColorCode,
  parseResponseToTable,
  displayTable,
  queryVictoriaMetrics,
  queryVictoriaMetricsSingle,
  sendSlackMessage,
  formatTableForSlack,
  getColorSummary,
  formatTableRow,
  getTableHeader,
  calculateColumnWidths,
};
