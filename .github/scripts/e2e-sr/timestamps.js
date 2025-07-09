/**
 * Timestamp utilities for E2E Success Rate reporting
 */

/**
 * Get time range configuration with custom or default values
 * @param {number} customFrom - Optional custom from timestamp (Unix seconds)
 * @param {number} customTo - Optional custom to timestamp (Unix seconds)
 * @returns {Object} Object containing time range config for VictoriaMetrics and Grafana
 */
function getTimeRangeConfig(customFrom = null, customTo = null) {
  let from, to, durationSeconds;

  if (customFrom && customTo) {
    // Use custom values
    from = customFrom;
    to = customTo;
    durationSeconds = to - from;
    console.log(`📅 Using CUSTOM time range:`);
  } else {
    // Use default 24-hour range
    const now = new Date();
    to = Math.floor(now.getTime() / 1000); // Current time in seconds (Unix timestamp)
    from = to - 24 * 60 * 60; // 24 hours ago in seconds
    durationSeconds = 86400; // 24 hours in seconds
    console.log(`📅 Using DEFAULT time range (24 hours):`);
  }

  // Generate Grafana URL with timestamps in milliseconds
  const grafanaLink = `https://vajra.razorpay.com/d/da1f638d-0c58-49f8-a902-0fcd3e6a35f8/merchant-dashboard-e2e-success-rate?orgId=1&from=${
    from * 1000
  }&to=${to * 1000}&var-module_name=web%2Fjs%2Fnewauth%2Fe2e&var-web_module_name=onenav`;

  console.log(`  From: ${from} (${new Date(from * 1000).toISOString()})`);
  console.log(`  To: ${to} (${new Date(to * 1000).toISOString()})`);
  console.log(
    `  Duration: ${durationSeconds} seconds (${(durationSeconds / 3600).toFixed(1)} hours)`,
  );
  console.log(`🔗 Grafana Link: ${grafanaLink}`);

  return {
    from: from, // Unix timestamp in seconds
    to: to, // Unix timestamp in seconds
    durationSeconds: durationSeconds, // Duration in seconds
    grafanaLink: grafanaLink, // Grafana dashboard URL
  };
}

/**
 * Format timestamp for display
 * @param {number} timestamp - Timestamp in seconds
 * @returns {string} Formatted timestamp string
 */
function formatTimestamp(timestamp) {
  const date = new Date(timestamp * 1000); // Convert seconds to milliseconds
  const dateStr = date.toLocaleDateString('en-GB', {
    timeZone: 'Asia/Kolkata',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
  const timeStr = date.toLocaleTimeString('en-US', {
    timeZone: 'Asia/Kolkata',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  });
  return `${dateStr} (${timeStr}) IST`;
}

/**
 * Get time range display string
 * @param {Object} timeConfig - Time config object from getTimeRangeConfig()
 * @returns {string} Formatted time range string
 */
function getTimeRangeDisplay(timeConfig) {
  return `📅 Data Range: ${formatTimestamp(timeConfig.from)} to ${formatTimestamp(timeConfig.to)}`;
}

module.exports = {
  getTimeRangeConfig,
  formatTimestamp,
  getTimeRangeDisplay,
};
