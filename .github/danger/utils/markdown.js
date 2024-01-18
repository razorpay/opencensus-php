/**
 * Creates a markdown table header with provided headers.
 * @param {string[]} headers - Array of header strings.
 * @returns {string} - Markdown table header.
 */
const createMarkdownTableHeader = (headers) => {
  const header = `|${headers.join('|')}|\n`;
  const headerDivider = `|${'---|'.repeat(headers.length)}\n`;
  return `${header}${headerDivider}`;
};

/**
 * Creates a markdown table row with provided columns.
 * @param {string[]} columns - Array of column strings.
 * @returns {string} - Markdown table row.
 */
const createMarkdownTableRow = (columns) => `|${columns.join('|')}|\n`;

/**
 * Creates a markdown table with the provided table data.
 * @param {string[][]} tableData - 2D array representing the table data.
 * @returns {string} - Markdown table.
 */
const createMarkdownTable = (tableData) => {
  const [headers, ...dataRows] = tableData;
  const table = createMarkdownTableHeader(headers);
  const rows = dataRows.map(createMarkdownTableRow).join('');
  return `${table}${rows}`;
};

/**
 * Wraps the given text in bold HTML tags.
 * @param {string} text - Text to be wrapped in bold.
 * @returns {string} - Bolded text.
 */
const bold = (text) => `<b>${text}</b>`;

module.exports = {
  createMarkdownTable,
  createMarkdownTableRow,
  createMarkdownTableHeader,
  bold,
};
