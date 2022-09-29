// reffered from @razorpay/universe-doctor/src/configs/danger/utils/tableHelpers

const createMarkdownTableHeader = (headers) => {
  const header = `|${headers.join('|')}|\n`;

  // append ---| to |, headers.length times
  // this creates the divider for the header
  const headerDivider = `|${'---|'.repeat(headers.length)}\n`;

  return header.concat(headerDivider);
};

const createMarkdownTableRow = (columns) => {
  return `|${columns.join('|')}|\n`;
};

const createMarkdownTable = (tableData) => {
  let table = createMarkdownTableHeader(tableData[0]);

  const rows = tableData.slice(1).reduce((acc, current) => {
    return `${acc}${createMarkdownTableRow(current)}`;
  }, '');

  table = table.concat(rows);
  return table;
};

module.exports = {
  createMarkdownTable,
  createMarkdownTableRow,
  createMarkdownTableHeader,
};
