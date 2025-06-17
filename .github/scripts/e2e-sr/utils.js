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

  const tableData = response.data.result.map(item => {
    const group = item.metric[metricFieldName] || 'Unknown';
    const percentage = parseFloat(item.value[1]);
    const colorCode = getColorCode(percentage);
    
    return {
      group: group,
      successRate: percentage.toFixed(2),
      color: colorCode,
      status: `${colorCode} ${percentage.toFixed(2)}%`
    };
  });

  // Sort by success rate (ascending)
  tableData.sort((a, b) => parseFloat(a.successRate) - parseFloat(b.successRate));
  
  return tableData;
}

/**
 * Display formatted table in console
 * @param {Array} tableData - Array of table row data
 * @param {string} queryName - Name of the query for display
 */
function displayTable(tableData, queryName) {
  console.log(`\n📊 ${queryName} - Success Rate Table:`);
  console.log('═'.repeat(60));
  console.log('Group'.padEnd(25) + 'Success Rate'.padEnd(15) + 'Status');
  console.log('─'.repeat(60));
  
  tableData.forEach(row => {
    const groupName = row.group.padEnd(25);
    const rate = `${row.successRate}%`.padEnd(15);
    console.log(`${groupName}${rate}${row.status}`);
  });
  
  console.log('═'.repeat(60));
  console.log(`Total Groups: ${tableData.length}`);
  
  // Summary by color
  const summary = {
    green: tableData.filter(r => r.color === '🟢').length,
    amber: tableData.filter(r => r.color === '🟠').length,
    red: tableData.filter(r => r.color === '🔴').length
  };
  
  console.log(`🟢 Green (>90%): ${summary.green}`);
  console.log(`🟠 Amber (50-90%): ${summary.amber}`);
  console.log(`🔴 Red (<50%): ${summary.red}`);
}

/**
 * Query Victoria Metrics API with the given parameters
 * @param {string} queryName - Name of the query for logging
 * @param {string} queryData - Query string to execute
 * @param {string} metricFieldName - Field name to extract from metrics (default: 'group')
 * @returns {Object} Object containing response, tableData, and queryName
 */
async function queryVictoriaMetrics(queryName, queryData, metricFieldName = 'group') {
  const apiUrl = 'https://victoriametrics-cold-ops.razorpay.com/select/447581938/prometheus/api/v1/query';
  
  const formData = new URLSearchParams();
  formData.append('query', queryData);
  formData.append('step', '2m0s'); // Match Grafana step parameter exactly
  
  const requestOptions = {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'Accept': 'application/json'
    },
    body: formData.toString()
  };

  try {
    console.log(`\n🚀 Executing ${queryName}...`);
    const response = await fetch(apiUrl, requestOptions);
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    
    const jsonResponse = await response.json();
    
    console.log(`✅ ${queryName} - Status:`, response.status);
    
    // Parse and display as table with configurable metric field
    const tableData = parseResponseToTable(jsonResponse, queryName, metricFieldName);
    displayTable(tableData, queryName);
    
    return { response: jsonResponse, tableData, queryName };
    
  } catch (error) {
    console.error(`❌ ${queryName} failed:`, error.message);
    throw error;
  }
}

/**
 * Send a message to Slack channel
 * @param {string} message - Message to send
 * @param {string} slackToken - Slack bot token
 * @returns {boolean} Success status
 */
async function sendSlackMessage(message, slackToken) {
  const channelId = "C0156ULAEFQ";
  
  if (!slackToken) {
    console.error('❌ DASHBOARD_E2E_SLACK_BOT_TOKEN environment variable is not set');
    return false;
  }

  const slackPayload = {
    channel: channelId,
    text: message
  };

  try {
    const response = await fetch('https://slack.com/api/chat.postMessage', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${slackToken}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(slackPayload)
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
 * Format table data for Slack display
 * @param {Array} tableData - Array of table row data
 * @param {string} queryName - Name of the query for display
 * @returns {string} Formatted Slack message
 */
function formatTableForSlack(tableData, queryName) {
  if (!tableData || tableData.length === 0) {
    return `:bar_chart: *${queryName}* - Summary: 🟢 0 | 🟠 0 | 🔴 0\n❌ No data available\n\n`;
  }
  
  // Summary by color
  const summary = {
    green: tableData.filter(r => r.color === '🟢').length,
    amber: tableData.filter(r => r.color === '🟠').length,
    red: tableData.filter(r => r.color === '🔴').length
  };
  
  let slackMessage = `:bar_chart: *${queryName}* - Summary: 🟢 ${summary.green} | 🟠 ${summary.amber} | 🔴 ${summary.red}\n`;
  
  // Add table header
  slackMessage += '```\n';
  slackMessage += 'Group'.padEnd(25) + 'Success Rate'.padEnd(15) + 'Status\n';
  slackMessage += '─'.repeat(60) + '\n';
  
  // Add table rows
  tableData.forEach(row => {
    const groupName = row.group.padEnd(25);
    const rate = `${row.successRate}%`.padEnd(15);
    slackMessage += `${groupName}${rate}${row.color}\n`;
  });
  
  slackMessage += '```\n\n';
  
  return slackMessage;
}

module.exports = {
  getColorCode,
  parseResponseToTable,
  displayTable,
  queryVictoriaMetrics,
  sendSlackMessage,
  formatTableForSlack
};