import { programsResponse } from 'merchant/views/GCMS/Programs/__tests__/mocks/fixtures';
import { daysToMonths, getProgramDenomination } from 'merchant/views/GCMS/shared/utils';

const DAYS = 30;
describe('gcms:utils', () => {
  test('daysToMonths', () => {
    const months = daysToMonths(DAYS);
    expect(months).toBe(1);
  });

  test('getProgramDenomination', () => {
    const denomination = getProgramDenomination({
      /* @ts-expect-error */
      policy: programsResponse.data.items[0].policies,
    });
    expect(denomination).toBe('Any');
  });
});
