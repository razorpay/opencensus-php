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
      iconColor: 'feedback.icon.negative.lowContrast',
      textColor: 'feedback.text.negative.lowContrast',
      label: 'Higher than industry average',
    };
  } else if (actualValue < comparedValue) {
    return {
      iconColor: 'surface.text.subdued.lowContrast',
      textColor: 'surface.text.subdued.lowContrast',
      label: 'Lower than industry average',
    };
  } else {
    return {
      iconColor: 'surface.text.subdued.lowContrast',
      textColor: 'surface.text.subdued.lowContrast',
      label: 'At par with industry average',
    };
  }
};
