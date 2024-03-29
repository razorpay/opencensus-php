import React, { Suspense } from 'react';
import { useTimePicker } from 'merchant_common/views/Reports/components/TimePicker/hooks/useTimePicker';
import { SelectedRangeInputField } from 'merchant_common/views/Reports/components/TimePicker/styled';
import { Box, ClockIcon, Spinner, Text, useTheme } from '@razorpay/blade/components';
import { FlexCentered } from 'merchant_common/views/Reports/components/styled';
import BaseTimePicker from './BaseTimePicker';

export const BaseTimeInput = ({ isValidated }: { isValidated: boolean }): JSX.Element => {
  const { selectedTime, setShowPicker, shouldShowPicker } = useTimePicker();
  const { theme } = useTheme();

  return (
    <Box position="relative">
      <SelectedRangeInputField
        theme={theme}
        aria-label={`Selected Time Is ${selectedTime.format('h:mm A')}`}
        onClick={() => setShowPicker(true)}
        validation={isValidated}
        focused={shouldShowPicker}
      >
        <Text size="medium" weight="regular" variant="body" color="surface.text.gray.normal">
          {selectedTime.format('h:mm A')}
        </Text>
        <FlexCentered
          style={{
            marginLeft: 5,
          }}
        >
          <ClockIcon color="feedback.icon.neutral.intense" size="medium" />
        </FlexCentered>
      </SelectedRangeInputField>
      {shouldShowPicker ? (
        <Suspense
          fallback={
            <Spinner
              labelPosition="bottom"
              size="medium"
              accessibilityLabel="Loading Component... Please wait..."
              right="35px"
              top="9.5px"
              position="absolute"
            />
          }
        >
          <BaseTimePicker />
        </Suspense>
      ) : null}
    </Box>
  );
};
