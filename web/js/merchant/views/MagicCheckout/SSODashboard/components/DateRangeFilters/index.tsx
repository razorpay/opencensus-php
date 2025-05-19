import React from 'react';
import moment from 'moment';
import type { TimeRange } from 'merchant/views/MagicCheckout/SSODashboard/types';

import {
  DATE_RANGE_PRESETS,
  DEFAULT_PRESET,
} from 'merchant/views/MagicCheckout/SSODashboard/components/DateRangeFilters/constants';

import { Box } from '@razorpay/blade/components';
import DateRangePicker from 'common/ui/DateRangePicker';

interface DateRangeFiltersProps {
  setTimeRange: (timeRange: TimeRange) => void;
}

const DateRangeFilters = ({ setTimeRange }: DateRangeFiltersProps) => {
  const presetList = DATE_RANGE_PRESETS;

  const onDatesChange = (start: moment.Moment, end: moment.Moment) => {
    setTimeRange({ start, end });
  };

  const isOutsideRange = (day: moment.Moment) =>
    day.isAfter(moment()) || day.isBefore(moment().subtract(91, 'days')); // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232

  return (
    <Box display="flex" marginBottom="spacing.7" zIndex={10}>
      <DateRangePicker
        presets={presetList}
        defaultPreset={DEFAULT_PRESET}
        onDatesChange={onDatesChange}
        isOutsideRange={isOutsideRange}
        hideCustomPreset
      />
    </Box>
  );
};

export default DateRangeFilters;
