import React, { useMemo } from 'react';
import { DateRangePicker } from '.';
import { DateRangePickerPropsType } from 'merchant_common/views/Reports/components/types';
import { useDateTimeRangeContext } from 'merchant_common/views/Reports/components/DateTimeRangePicker/context/DateTimeRangePickerContext';
import { useMediaQuery } from 'merchant_common/views/Reports/hooks';

// month picker, year picker doesn't depend on startDate, memoizing here to improve the perf
// otherwise useDateTimeRangeContext will cause rerender if directly used in DateRangePicker
const MemoisedDateRangePicker = React.memo(DateRangePicker);

export const VisibleRangeContainer = (props: DateRangePickerPropsType) => {
  const { startDate } = useDateTimeRangeContext();
  const isCompactView = useMediaQuery(`(max-width: 550px)`).breakpointMatched;

  const initialVisibleRange = useMemo(
    () =>
      startDate
        ? isCompactView
          ? [startDate.clone().startOf('day')]
          : [startDate.clone().startOf('day'), startDate.clone().endOf('day').add(1, 'month')]
        : null,
    [isCompactView],
  );

  return (
    <MemoisedDateRangePicker
      {...props}
      initialVisibleRange={startDate ? initialVisibleRange : null}
    />
  );
};
