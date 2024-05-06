import moment from 'moment';

import { RATIO_DURATION } from 'merchant/views/RiskAndFraud/RiskAnalytics/EntityOverview/constants';
import {
  getRatioPayload,
  getLabelComparision,
  calculatePercentageChange,
  getComparisonData,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/EntityOverview/utils';

const MOCK_DATE = '2024-03-08';

jest.mock('moment', () => {
  const mockedMoment = jest.requireActual('moment');
  return () => mockedMoment(`${MOCK_DATE}T00:00:00Z`);
});

describe('getRatioPayload', () => {
  it('should return the correct payload for today', () => {
    const { duration, unit } = RATIO_DURATION;
    const expectedEndDate = moment(MOCK_DATE).startOf('day').unix();
    const expectedStartDate = moment(MOCK_DATE).subtract(duration, unit).startOf('day').unix();
    const payload = getRatioPayload();
    expect(payload.endDate).toBe(expectedEndDate);
    expect(payload.startDate).toBe(expectedStartDate);
  });
});

test('getLabelComparision returns correct results', () => {
  // Test case 1: actual value is higher than compared value
  expect(getLabelComparision(10, 5)).toEqual({
    iconColor: 'feedback.icon.negative.intense',
    textColor: 'feedback.text.negative.intense',
    label: 'Higher than industry average',
  });

  // Test case 2: actual value is lower than compared value
  expect(getLabelComparision(2, 8)).toEqual({
    iconColor: 'surface.icon.gray.muted',
    textColor: 'surface.text.gray.muted',
    label: 'Lower than industry average',
  });

  // Test case 3: actual value is equal to compared value
  expect(getLabelComparision(7, 7)).toEqual({
    iconColor: 'surface.icon.gray.muted',
    textColor: 'surface.text.gray.muted',
    label: 'At par with industry average',
  });
});

describe('calculatePercentageChange', () => {
  test.each([
    [50, 70, 40],
    [50, 30, -40],
    [50, 50, 0],
    [0, 10, 100],
    [0, 0, 0],
  ])(
    'should calculate percentage change correctly when industry average is %s and entity value is %s',
    (industryAverage, entityValue, expectedOutput) => {
      const result = calculatePercentageChange(industryAverage, entityValue);
      expect(result).toEqual(expectedOutput);
    },
  );
});

describe('getComparisonData', () => {
  test.each([
    [50, 70, 'higher than industry average'],
    [50, 30, 'lower than industry average'],
    [50, 50, 'at par with industry average'],
  ])(
    'should calculate percentage change correctly when industry average is %s and entity value is %s',
    (industryAverage, entityValue, expectedOutput) => {
      const result = getComparisonData(industryAverage, entityValue);
      expect(result).toEqual(expectedOutput);
    },
  );
});
