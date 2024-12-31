import { calculatePercentage } from '@apps/digital-bills/src/utils/helpers/calculatePercentage';

describe('calculatePercentage', () => {
  test('should return the percentage value for the passed inputs', () => {
    expect(calculatePercentage(10, 100)).toEqual(10);
  });
});
