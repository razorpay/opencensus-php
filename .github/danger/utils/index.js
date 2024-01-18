const printMessage = require('@razorpay/universe-doctor/src/configs/danger/utils/printMessage');
const universeUsage = require('@razorpay/universe-utils/universeUsage').default;
const Logger = require('./logger');
const {
  createMarkdownTable,
  createMarkdownTableRow,
  createMarkdownTableHeader,
  bold,
} = require('./markdown');

/**
 * Converts bytes to kilobytes.
 * @param {number} bytes - The size in bytes.
 * @param {number} [decimals=2] - The number of decimal places (optional, default is 2).
 * @returns {number} - The size in kilobytes.
 */
const bytesToKB = (bytes, decimals = 2) => {
  if (!+bytes) return 0;
  return parseFloat((bytes / 1024).toFixed(decimals));
};

/**
 * Returns status with or without an icon.
 * @param {boolean} result - The result status.
 * @param {boolean} [withIcon=true] - Whether to include an icon (optional, default is true).
 * @returns {string} - The status with an optional icon.
 */
const getStatus = (result, withIcon = true) =>
  result ? `${withIcon ? '✅ ' : ''}Pass` : `${withIcon ? '❌ ' : ''}Fail`;

/**
 * Shows the threshold with a warning emoji based on the value.
 * @param {object} options - The options object.
 * @param {number} options.usedThreshold - The used threshold percentage.
 * @returns {string} - The threshold value with an optional emoji.
 */
const showThreshold = ({ usedThreshold }) =>
  `${usedThreshold} % ${usedThreshold > 100 ? '🚨' : usedThreshold > 95 ? '🚧' : ''}`;

module.exports = {
  bytesToKB,
  getStatus,
  showThreshold,
  printMessage,
  pr: global.danger.github.pr,
  universeUsage,
  Logger,
  createMarkdownTable,
  createMarkdownTableRow,
  createMarkdownTableHeader,
  bold,
};
