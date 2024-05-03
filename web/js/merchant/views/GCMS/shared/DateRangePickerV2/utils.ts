import moment from 'moment';

import { getStartDateFromDiff } from 'common/utils/rzp-utils';

import { PresetOption } from './DateRangePickerV2';

// ['Past 7 Days', -7, 'days'],
// ['Past 30 Days', -30, 'days'],
// ['Past 90 Days', -90, 'days'],
export const generatePresets = (
  presets: [string, number, string][],
  minStartDate?: number,
): PresetOption[] => {
  const now = moment();
  return presets.map((preset) => {
    const text = preset[0];
    const rest = preset.slice(1);
    const timeStampDiff =
      now.unix() -
      now
        .clone()
        .add(...rest)
        .unix();

    //to disable options if the timeStampDiff is before of the minStartDate
    const isDisabled = minStartDate
      ? getStartDateFromDiff(timeStampDiff, now) < minStartDate
      : false;

    const result = { name: text, value: timeStampDiff, disabled: isDisabled };

    return result;
  });
};
