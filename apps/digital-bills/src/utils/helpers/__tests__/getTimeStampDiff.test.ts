import getTimeStampDiff from '@apps/digital-bills/src/utils/helpers/getTimeStampDiff';

const mocks = {
  timestampA: '2024-02-08T18:29:59.000Z',
  timestampB: '2024-02-07T18:29:59.000Z',
};

describe('getTimeStampDiff', () => {
  test('should return correct difference in ms', () => {
    expect(getTimeStampDiff(mocks.timestampA, mocks.timestampB)).toEqual(86400000);
  });
});
