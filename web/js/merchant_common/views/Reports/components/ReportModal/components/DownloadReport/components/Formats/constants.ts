export const FORMATS_PLACEHOLDER = 'Excel, CSV or More';

export const DELIMITER_PLACEHOLDER = 'Comma or Pipe';
export const DELIMITER_ERROR_TEXT =
  'Mandatory Field: Select the delimiter in which you want to receive the report in.';

// Available default Formats array.
export const DEFAULT_FORMATS = [
  {
    label: 'CSV',
    value: 'csv',
  },
  {
    label: 'Excel',
    value: 'xlsx',
  },
  {
    label: 'Txt',
    value: 'txt',
  },
];

export const RPT_FORMAT = {
  label: 'RPT',
  value: 'rpt',
};

const DELIMITERS = [
  { label: 'Comma', value: ',' },
  { label: 'Pipe', value: '|' },
];

export const DELIMITER_SUPPORT_MAP = {
  csv: [DELIMITERS[0]],
  txt: DELIMITERS,
  rpt: DELIMITERS.filter(({ label }) => label === 'Pipe'),
};
