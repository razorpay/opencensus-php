import moment from 'moment';

import { RATIO_DURATION } from './constants';
import { LabelComparisonResult, RatioPayload } from './types';

export const getRatioPayload = (): RatioPayload => {
  const { duration, unit } = RATIO_DURATION;
  return {
    endDate: moment().startOf('day').unix(),
    startDate: moment().subtract(duration, unit).startOf('day').unix(),
  };
};

export const getLabelComparision = (
  actualValue: number,
  comparedValue: number,
): LabelComparisonResult => {
  if (actualValue > comparedValue) {
    return {
      iconColor: 'feedback.icon.negative.intense',
      textColor: 'feedback.text.negative.intense',
      label: 'Higher than industry average',
    };
  } else if (actualValue < comparedValue) {
    return {
      iconColor: 'surface.icon.gray.muted',
      textColor: 'surface.text.gray.muted',
      label: 'Lower than industry average',
    };
  } else {
    return {
      iconColor: 'surface.icon.gray.muted',
      textColor: 'surface.text.gray.muted',
      label: 'At par with industry average',
    };
  }
};

export const calculatePercentageChange = (industryAverage: number, entityValue: number) => {
  if (industryAverage === 0 && entityValue === 0) {
    return 0;
  }

  if (industryAverage === 0) {
    return 100;
  }

  const decimalIndustryAverage = industryAverage / 100;
  const decimalEntityValue = entityValue / 100;
  const percentageChange =
    ((decimalEntityValue - decimalIndustryAverage) / Math.abs(decimalIndustryAverage)) * 100;

  return Number(percentageChange.toFixed(2));
};

export const getComparisonData = (industryAverage: number, entityValue: number): string => {
  const percentageDifference = calculatePercentageChange(industryAverage, entityValue);
  if (percentageDifference > 0) {
    return `higher than industry average`;
  }
  if (percentageDifference < 0) {
    return `lower than industry average`;
  }
  return 'at par with industry average';
};
