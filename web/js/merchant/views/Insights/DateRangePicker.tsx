import React, { useState } from 'react';
import { DatePicker } from '@razorpay/blade/components';
import moment from 'moment';
import { insightsDateRangePresets } from 'merchant/views/Insights/constants';

const DateRangePicker = ({
  selectedDateCallback,
}: {
  selectedDateCallback: (date: { from: number; to: number }) => void;
}) => {
  const [selectedPreset, setSelectedPreset] = useState<{
    value: { from: Date; to: Date };
  }>({
    value: {
      from: moment().clone().subtract(0, 'days').startOf('day').toDate(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
      to: moment().clone().endOf('day').toDate(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
    },
  });

  const onDatesChange = (from: Date, to: Date) => {
    setSelectedPreset({ value: { from, to } });
  };

  const onApply = (from: Date, to: Date) => {
    setSelectedPreset({ value: { from, to } });
    selectedDateCallback({
      from: moment(from).clone().unix(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
      to: moment(to).clone().unix(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
    });
  };

  return (
    <DatePicker
      // @ts-ignore
      label={{ start: 'Start Date', end: 'End Date' }}
      // @ts-ignore
      selectionType="range"
      value={[selectedPreset.value.from, selectedPreset.value.to]}
      presets={insightsDateRangePresets}
      minDate={moment().clone().subtract(30, 'days').startOf('day').toDate()}
      maxDate={moment().clone().endOf('day').toDate()}
      onChange={(date) => {
        onDatesChange(date[0], date[1]);
      }}
      onApply={(date) => {
        onApply(date[0], date[1]);
      }}
    />
  );
};

export { DateRangePicker };
