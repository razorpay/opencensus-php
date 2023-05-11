import React from 'react';
import { CalendarContent, CalendarField } from './styled';
import { CalendarFooter } from './components/CalendarFooter';
import { DAY_SIZE } from './constants';
import { DateTimeRangeContainer } from './components/DateTimeRangeContainer';
import { DateTimeRangeHeader } from './components/DateTimeRangeHeader';
import { DateTimeRangePickerPropsType } from './types';
import { DateTimeRangePickerProvider } from './context/DateTimeRangePickerContext';
import { DatesContextProvider } from './context/DatesContext';
import { SelectedRangeInfoInput } from './components/SelectedRangeInfoInput';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { VisibleRangeContainer } from './components/VisibleRangeContainer';
import { FieldFooter } from 'merchant_common/views/Reports/components/FieldFooter';
import { FieldLabel } from 'merchant_common/views/Reports/components/FieldLabel';

const Picker = ({
  allowSingleDateSelection = false,
  disableFuture = false,
  disablePast = false,
  helpText,
  label,
  maxDate,
  minDate,
  onChange,
  placeHolder,
  selectedRangeFormat,
  showToday,
  validateRange = () => true,
  validationState = true,
  value,
  ariaLabel,
  modifiers,
  necessityIndicator,
  errorText,
}: DateTimeRangePickerPropsType): JSX.Element => {
  const { theme } = useTheme();
  const daySize = DAY_SIZE;
  return (
    <DateTimeRangePickerProvider value={value}>
      <DatesContextProvider>
        <CalendarField>
          <FieldLabel necessityIndicator={necessityIndicator} label={label} />
          <SelectedRangeInfoInput
            placeHolder={placeHolder}
            value={value}
            onChange={onChange}
            label={label}
            selectedRangeFormat={selectedRangeFormat}
            validateRange={validateRange}
            isValidatedField={validationState}
          >
            <DateTimeRangeContainer>
              <DateTimeRangeHeader />
              <CalendarContent aria-label={ariaLabel} theme={theme}>
                <VisibleRangeContainer
                  showToday={showToday}
                  disablePast={disablePast}
                  daySize={daySize}
                  disableFuture={disableFuture}
                  allowSingleDateSelection={allowSingleDateSelection}
                  maxDate={maxDate}
                  minDate={minDate}
                />
              </CalendarContent>
              <CalendarFooter
                disableFuture={disableFuture}
                disablePast={disablePast}
                modifiers={modifiers}
              />
            </DateTimeRangeContainer>
          </SelectedRangeInfoInput>
          <FieldFooter errorText={errorText} helpText={helpText} validation={validationState} />{' '}
        </CalendarField>
      </DatesContextProvider>
    </DateTimeRangePickerProvider>
  );
};

// pass onChange to this using a useCallback hook, otherwise it will rerender
// this picker
export const DateTimeRangePicker = React.memo(Picker);
