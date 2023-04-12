import React from 'react';
import moment from 'moment';
import { CalendarFooter as StyledCalendarFooter } from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';
import { Text } from 'merchant_common/views/Reports/components';
import { useDateTimeRangeContext } from 'merchant_common/views/Reports/components/DateTimeRangePicker/context/DateTimeRangePickerContext';
import { FooterProps } from 'merchant_common/views/Reports/components/DateTimeRangePicker/types';

export const CalendarFooter = ({
  disablePast,
  disableFuture,
  modifiers = {},
}: FooterProps): JSX.Element => {
  const { validationError } = useDateTimeRangeContext();

  const renderNotice = () => {
    switch (true) {
      case Boolean(validationError):
        return validationError;
      case disableFuture:
        if (modifiers?.INFO_WHEN_FUTURE_DISABLED) {
          return modifiers?.INFO_WHEN_FUTURE_DISABLED;
        } else {
          return `*Maximum end date allowed is ${moment().endOf('day').format('MMMM Do, h A')}`;
        }
      case disablePast:
        if (modifiers?.INFO_WHEN_PAST_DISABLED) {
          return modifiers?.INFO_WHEN_PAST_DISABLED;
        } else {
          return `*Minimum start date allowed is ${moment().startOf('day').format('MMMM Do, h A')}`;
        }
      default:
        if (modifiers?.DEFAULT_INFO) {
          return modifiers.DEFAULT_INFO;
        } else {
          return '';
        }
    }
  };

  return (
    <StyledCalendarFooter>
      <Text
        variant="caption"
        type="normal"
        weight="bold"
        color={
          Boolean(validationError)
            ? 'feedback.text.negative.lowContrast'
            : 'feedback.text.information.lowContrast'
        }
      >
        {renderNotice()}
      </Text>
    </StyledCalendarFooter>
  );
};
