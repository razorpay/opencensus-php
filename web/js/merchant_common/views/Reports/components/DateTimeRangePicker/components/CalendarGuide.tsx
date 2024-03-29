import React from 'react';
import { CalendarGuideContainer } from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';
import { Text } from 'merchant_common/views/Reports/components';
import { useDateTimeRangeContext } from 'merchant_common/views/Reports/components/DateTimeRangePicker/context/DateTimeRangePickerContext';
import { FooterProps } from 'merchant_common/views/Reports/components/DateTimeRangePicker/types';
import { parseError } from 'merchant_common/views/Reports/components/DateTimeRangePicker/utils';

export const CalendarGuide = ({
  disablePast,
  disableFuture,
  modifiers = {},
}: FooterProps): JSX.Element => {
  const { validationError } = useDateTimeRangeContext();

  const validationErrorText = parseError({
    disableFuture,
    disablePast,
    modifiers,
    validationError,
  });

  return (
    <CalendarGuideContainer>
      <Text
        variant="caption"
        weight="semibold"
        color={
          Boolean(validationError)
            ? 'feedback.text.negative.intense'
            : 'feedback.text.information.intense'
        }
      >
        {validationErrorText}
      </Text>
    </CalendarGuideContainer>
  );
};
