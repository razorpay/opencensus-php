import React, { useRef, useState } from 'react';
import moment from 'moment';
import {
  CalendarInput,
  SelectedDateValue,
} from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';
import { SELECTED_DATE_RANGE_RENDER_FORMAT } from 'merchant_common/views/Reports/components/DateTimeRangePicker/constants';
import { SelectedRangeInfoInputPropsType } from 'merchant_common/views/Reports/components/DateTimeRangePicker/types';
import { Spinner, Text } from 'merchant_common/views/Reports/components';
import { didDatesUpdate } from 'merchant_common/views/Reports/components/DateTimeRangePicker/utils';
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
  validate,
}: SelectedRangeInfoInputPropsType): JSX.Element => {
  const [isPickerOpen, setPickerOpen] = useState(false);
  const { startDate, endDate, setValidationError } = useDateTimeRangeContext();
  const dateTimeRangePickerContainerRef = useRef<HTMLDivElement>(null);
  const validValue = value && value.startDate && value.endDate;

  const validateRange = () => {
    if (startDate && endDate && validate) {
      const isValid = validate({ startDate, endDate });
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
    if (value) {
      const renderText = `${moment(value.startDate).format(
        selectedRangeFormat ?? SELECTED_DATE_RANGE_RENDER_FORMAT,
      )} - ${moment(value.endDate).format(
        selectedRangeFormat ?? SELECTED_DATE_RANGE_RENDER_FORMAT,
      )}`;
      return (
        <Text
          variant="body"
          type="normal"
          weight="regular"
          color={validValue ? 'surface.text.subtle.lowContrast' : 'surface.text.muted.lowContrast'}
        >
          {validValue ? renderText : placeHolder ?? 'Loading...'}
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
            Loading... Please wait...
          </Text>
          <Spinner size="medium" accessibilityLabel="Picker loading..." />
        </FlexCentered>
      );
    }
  };

  useClickOutSide([dateTimeRangePickerContainerRef], () => {
    if (startDate && endDate) {
      const { error, isValid } = validateRange();
      if (isValid) {
        if (didDatesUpdate(value, { startDate, endDate })) onChange({ startDate, endDate });
        setValidationError('');
        setPickerOpen(false);
      } else {
        setValidationError(error);
      }
    }
  });

  return (
    <CalendarInput ref={dateTimeRangePickerContainerRef} label={label} open={isPickerOpen}>
      <SelectedDateValue
        aria-label="Picker Input Field"
        onClick={value ? () => setPickerOpen(true) : undefined}
      >
        {renderDate()}
      </SelectedDateValue>
      <div aria-label="Picker Container">{isPickerOpen ? children : null}</div>
    </CalendarInput>
  );
};
