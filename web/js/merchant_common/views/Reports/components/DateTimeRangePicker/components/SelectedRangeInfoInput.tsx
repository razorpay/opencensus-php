import React, { useRef, useState } from 'react';
import {
  CalendarInput,
  SelectedDateValue,
} from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';
import { SelectedRangeInfoInputPropsType } from 'merchant_common/views/Reports/components/DateTimeRangePicker/types';
import { CalendarIcon, Text } from 'merchant_common/views/Reports/components';
import {
  didDatesUpdate,
  getFormattedDate,
} from 'merchant_common/views/Reports/components/DateTimeRangePicker/utils';
import { useClickOutSide } from 'common/utils/customHooks';
import { useDateTimeRangeContext } from 'merchant_common/views/Reports/components/DateTimeRangePicker/context/DateTimeRangePickerContext';
import { FlexCentered } from 'merchant_common/views/Reports/components/styled';

export const SelectedRangeInfoInput = ({
  children,
  value,
  onChange,
  placeHolder,
  label,
  selectedRangeFormat,
  validateRange,
  isValidatedField,
}: SelectedRangeInfoInputPropsType): JSX.Element => {
  const [isPickerOpen, setPickerOpen] = useState(false);
  const { startDate, endDate, setValidationError } = useDateTimeRangeContext();
  const dateTimeRangePickerContainerRef = useRef<HTMLDivElement>(null);
  const validValue = value && value.startDate && value.endDate;

  const validateSelectedRange = () => {
    if (startDate && !endDate) {
      return {
        isValid: false,
        error: 'Please select an end date.',
      };
    } else if (startDate && endDate && startDate.isAfter(endDate, 'minute')) {
      return {
        isValid: false,
        error: 'Please select a valid range.',
      };
    } else if (startDate && endDate && validateRange) {
      const isValid = validateRange({ startDate, endDate });
      const isError = isValid && typeof isValid === 'boolean' ? false : isValid.error;

      if (isError) {
        return {
          isValid: false,
          error: isError,
        };
      } else {
        return {
          isValid: true,
          error: '',
        };
      }
    } else {
      return {
        isValid: true,
        error: '',
      };
    }
  };

  const renderDate = () => {
    if (validValue) {
      const renderText = getFormattedDate(value, selectedRangeFormat);
      return (
        <Text variant="body" type="normal" weight="regular" color="surface.text.subtle.lowContrast">
          {renderText}
        </Text>
      );
    } else {
      return (
        <FlexCentered
          style={{
            justifyContent: 'space-between',
          }}
        >
          <Text
            variant="body"
            type="normal"
            weight="regular"
            color="surface.text.muted.lowContrast"
          >
            {placeHolder ?? 'Loading... Please wait...'}
          </Text>
        </FlexCentered>
      );
    }
  };

  useClickOutSide([dateTimeRangePickerContainerRef], () => {
    const { error, isValid } = validateSelectedRange();
    if (isValid) {
      if (didDatesUpdate(value, { startDate, endDate }) && startDate && endDate)
        onChange({ startDate, endDate });
      setValidationError('');
      setPickerOpen(false);
    } else {
      setValidationError(error);
    }
  });

  return (
    <CalendarInput ref={dateTimeRangePickerContainerRef} label={label} open={isPickerOpen}>
      <SelectedDateValue
        validation={isValidatedField}
        aria-label="Picker Input Field"
        focused={isPickerOpen}
        onClick={() => setPickerOpen(true)}
      >
        <div>{renderDate()}</div>
        <CalendarIcon color="feedback.icon.neutral.lowContrast" size="medium" />
      </SelectedDateValue>
      <div aria-label="Picker Container">{isPickerOpen ? children : null}</div>
    </CalendarInput>
  );
};
