import React from 'react';
import { Box } from '@razorpay/blade/components';

import RadioButtonGroup from 'common/components/RadioButtonGroup';
import { getChartInterval } from 'merchant/views/RiskAndFraud/RiskAnalytics/utils';

import type { DateRange, IntervalValue } from 'merchant/views/RiskAndFraud/RiskAnalytics/types';

export interface ChartIntervalProps {
  isLoading: boolean;
  dateRange: DateRange;
  selectedInterval: IntervalValue;
  handleInterval?: (value: IntervalValue) => void;
}

const ChartInterval: React.FC<ChartIntervalProps> = (props) => {
  const { isLoading, dateRange, selectedInterval, handleInterval } = props;
  const { preset } = dateRange;
  const intervalOptions = getChartInterval(preset.value);

  const handleRadioChange = (value: string) => {
    const intervalsValue = value as IntervalValue;
    if (handleInterval) {
      handleInterval(intervalsValue);
    }
  };

  return (
    <Box
      display="flex"
      flexDirection="row"
      justifyContent="right"
      marginBottom="spacing.7"
      paddingRight="58px"
    >
      <RadioButtonGroup
        testID="chart-interval"
        options={intervalOptions}
        isDisabled={isLoading}
        selectedOption={selectedInterval}
        onChange={handleRadioChange}
      />
    </Box>
  );
};

export default ChartInterval;
