import React, { useState } from 'react';
import { Box, DatePicker } from '@razorpay/blade/components';
import moment from 'moment';
import DateRangePickerV2 from 'common/ui/DateRangePickerV2/DateRangePickerV2';
import { insightxDateRangePresets } from './constants';

const DateRangePicker = ({
  selectedDateCallback,
}: {
  selectedDateCallback: (date: { from: number; to: number }) => void;
}) => {
  const [selectedPreset, setSelectedPreset] = useState<{
    value: { from: Date; to: Date };
  }>({
    value: {
      from: moment().subtract(0, 'days').startOf('day').toDate(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
      to: moment().endOf('day').toDate(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
    },
  });

  const onDatesChange = (from: Date, to: Date) => {
    setSelectedPreset({ value: { from: from, to: to } });
  };

  const onApply = (from: Date, to: Date) => {
    setSelectedPreset({ value: { from: from, to: to } });
    selectedDateCallback({
      from: moment(from).unix(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
      to: moment(to).unix(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
    });
  };

  return (
    <>
      <DatePicker
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        label={{ start: 'Start Date', end: 'End Date' }}
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        selectionType="range"
        value={[selectedPreset.value.from, selectedPreset.value.to]}
        presets={insightxDateRangePresets}
        minDate={moment().subtract(30, 'days').startOf('day').toDate()}
        maxDate={moment().endOf('day').toDate()}
        onChange={(date) => {
          onDatesChange(date[0], date[1]);
        }}
        onApply={(date) => {
          onApply(date[0], date[1]);
        }}
      />
    </>
  );
};

export { DateRangePicker };
