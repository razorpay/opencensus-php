import getBillUrl from '@apps/digital-bills/src/utils/helpers/getBillUrl';

describe('getBillUrl', () => {
  test('should return Bill URL for the passed in bill ID', () => {
    expect(getBillUrl('1234')).toBe('https://yourbill.me/1234');
  });
});
