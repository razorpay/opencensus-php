import React, { useState } from 'react';
import { Box, Heading, DatePicker } from '@razorpay/blade/components';

import {
  getDateRangePresets,
  getSelectedDatesFromPicker,
} from '@apps/digital-bills/src/utils/helpers/getDateRangePresets';
import getTimeStampDiff from '@apps/digital-bills/src/utils/helpers/getTimeStampDiff';
import { MAX_DATE_FOR_DATE_PICKER } from '@apps/digital-bills/src/utils/constants';

type OverviewHeaderProps = {
  overviewTimeRange: { fromDate: string; toDate: string };
  setOverviewTimeRange: (start: string, end: string) => void;
};

const OverviewHeader = ({ overviewTimeRange, setOverviewTimeRange }: OverviewHeaderProps) => {
  const [showMaxRangeError, setShowMaxRangeError] = useState(false);

  const handleDatesChange = (range) => {
    const { fromDate, toDate } = getSelectedDatesFromPicker(range);
    const daysDiff = getTimeStampDiff(toDate, fromDate, 'days');
    if (daysDiff >= 90) {
      setShowMaxRangeError(true);
    } else {
      setOverviewTimeRange(fromDate, toDate);
      if (showMaxRangeError) {
        setShowMaxRangeError(false);
      }
    }
  };

  return (
    <Box
      display="flex"
      flexDirection={{ base: 'column', l: 'row' }}
      alignItems={{ base: 'flex-start', l: 'center' }}
      justifyContent={{ base: 'flex-start', l: 'space-between' }}
      marginBottom="spacing.4"
    >
      <Box flex={1.5}>
        <Heading size="large" weight="semibold">
          Overview
        </Heading>
      </Box>
      <Box flex={2}>
        <DatePicker
          allowSingleDateInRange
          // @ts-expect-error
          label={{
            start: 'Duration',
          }}
          labelPosition="left"
          // @ts-expect-error
          selectionType="range"
          defaultValue={[
            new Date(overviewTimeRange?.fromDate),
            new Date(overviewTimeRange?.toDate),
          ]}
          onApply={handleDatesChange}
          presets={getDateRangePresets()}
          maxDate={MAX_DATE_FOR_DATE_PICKER}
          validationState={showMaxRangeError ? 'error' : 'none'}
          // @ts-expect-error
          errorText={{ start: "Range can't be more than 90 days" }}
        />
      </Box>
    </Box>
  );
};

export default OverviewHeader;
