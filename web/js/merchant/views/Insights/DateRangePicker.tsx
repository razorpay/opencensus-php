import React, { useState, useEffect } from 'react';
import { DatePicker } from '@razorpay/blade/components';
import moment from 'moment';

interface DateRange {
  from: number;
  to: number;
}

interface DateRangePickerProps {
  selectedDateCallback: (date: DateRange) => void;
  selectedDates?: DateRange;
  isLoading?: boolean;
}

const DateRangePicker: React.FC<DateRangePickerProps> = ({
  selectedDateCallback,
  selectedDates,
  isLoading = false,
}) => {
  const [internalSelectedDates, setInternalSelectedDates] = useState<{
    from: Date;
    to: Date;
  }>(() => {
    if (selectedDates) {
      return {
        from: moment.unix(selectedDates.from).toDate(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
        to: moment.unix(selectedDates.to).toDate(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
      };
    }

    return {
      from: moment().clone().subtract(0, 'days').startOf('day').toDate(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
      to: moment().clone().endOf('day').toDate(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
    };
  });

  useEffect(() => {
    if (selectedDates) {
      setInternalSelectedDates({
        from: moment.unix(selectedDates.from).toDate(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
        to: moment.unix(selectedDates.to).toDate(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
      });
    }
  }, [selectedDates]);

  const handleDatesChange = (from: Date, to: Date) => {
    setInternalSelectedDates({ from, to }); // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
  };

  const handleApply = (from: Date, to: Date) => {
    const newDateRange = {
      from,
      to,
    };

    setInternalSelectedDates(newDateRange);

    selectedDateCallback({
      from: moment(from).clone().unix(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
      to: moment(to).clone().add(1, 'day').startOf('day').unix(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
    });
  };

  return (
    <DatePicker
      // @ts-ignore
      selectionType="range"
      value={[internalSelectedDates.from, internalSelectedDates.to]}
      minDate={moment().clone().subtract(30, 'days').startOf('day').toDate()}
      maxDate={moment().clone().endOf('day').toDate()}
      isDisabled={isLoading}
      onChange={(date) => {
        handleDatesChange(date[0], date[1]);
      }}
      onApply={(date) => {
        handleApply(date[0], date[1]);
      }}
    />
  );
};

export { DateRangePicker };
export type { DateRangePickerProps };
