import React, { Suspense } from 'react';
import { useTimePicker } from 'merchant_common/views/Reports/components/TimePicker/hooks/useTimePicker';
import { SelectedRangeInfoBadge } from 'merchant_common/views/Reports/components/TimePicker/styled';
import { Box, ClockIcon, Spinner, Text } from 'merchant_common/views/Reports/components';
import { FlexCentered } from 'merchant_common/views/Reports/components/styled';
import DTRPTimePicker from './DTRPTimePicker';

export const DTRPTimeInput = ({ disableInput }: { disableInput: boolean }): JSX.Element => {
  const { selectedTime, setShowPicker, shouldShowPicker } = useTimePicker();

  return (
    <>
      {!disableInput ? (
        <SelectedRangeInfoBadge
          focused={Boolean(shouldShowPicker)}
          aria-label={`Selected Time Is ${selectedTime.format('h:mm A')}`}
          onClick={() => setShowPicker(true)}
        >
          <Text type="normal" size="medium" weight="regular" variant="body">
            {selectedTime.format('h:mm A')}
          </Text>
          <FlexCentered
            style={{
              marginLeft: 5,
            }}
          >
            <ClockIcon color="feedback.icon.neutral.lowContrast" size="medium" />
          </FlexCentered>
        </SelectedRangeInfoBadge>
      ) : null}
      {shouldShowPicker ? (
        <Suspense
          fallback={
            <Box
              height="90px"
              backgroundColor="surface.background.level2.lowContrast"
              width="202px"
              position="absolute"
              elevation="midRaised"
              borderRadius="medium"
              marginTop="spacing.2"
              left="calc(-100% - 6px)"
              zIndex={1}
              display="flex"
              alignItems="center"
              justifyContent="center"
            >
              <Spinner accessibilityLabel="Loading..." size="medium" />
            </Box>
          }
        >
          <DTRPTimePicker />
        </Suspense>
      ) : null}
    </>
  );
};
