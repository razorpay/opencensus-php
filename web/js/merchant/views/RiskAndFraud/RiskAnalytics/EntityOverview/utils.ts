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
