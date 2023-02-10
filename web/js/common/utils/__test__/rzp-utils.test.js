import { getCurrentFinancialYear, stringTemplate } from 'common/utils/rzp-utils';

describe('test for getCurrentFinancialYear', () => {
  it('should return correct financial year for 31st march', () => {
    jest.useFakeTimers('modern');
    jest.setSystemTime(new Date(2023, 2, 31));
    const currentYear = getCurrentFinancialYear();
    expect(currentYear).toBe(2022);
    jest.useRealTimers();
  });

  it('should return correct financial year for 1st april', () => {
    jest.useFakeTimers('modern');
    jest.setSystemTime(new Date(2023, 3, 1));
    const nextYear = getCurrentFinancialYear();
    expect(nextYear).toBe(2023);
    jest.useRealTimers();
  });
});

test('stringTemplate', () => {
  const str = '/notes/{category}?noteId={noteId}';
  const replacer = { category: 'development', noteId: '1' };

  expect(stringTemplate(str, replacer)).toBe('/notes/development?noteId=1');
});
