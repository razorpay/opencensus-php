export const mockToday = '2023-08-01T00:00:00Z';
export const mockLast7Days = '2023-07-25T00:00:00Z';
export const mockLast30Days = '2023-07-02T00:00:00Z';
export const mockLast90Days = '2023-05-03T00:00:00Z';
export const mockCurrentYearJanTillDate = '2023-01-01T00:00:00Z';
export const mockThisFinancialYear = '2023-04-01T00:00:00Z';
export const mockPrevFinancialYearLastQuarter = '2023-02-01T00:00:00Z';
export const mockPrevFinancialYear = '2022-04-01T00:00:00Z';

jest.mock('moment', () => {
  const moment = jest.requireActual('moment');
  return jest.fn((input) => moment(input || mockToday));
});
jest.mock('query-string', () => ({
  parse: jest.fn(() => ({})),
}));

jest.mock('common/utils/rzp-utils', () => ({
  ...jest.requireActual('common/utils/rzp-utils'),
  decodeSensitiveFields: jest.fn((arg) => arg),
}));

export const mockOptions = [
  { title: 'Option 1', value: 'option1' },
  { title: 'Option 2', value: 'option2' },
  { title: 'Option 3', value: 'option3' },
];
