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
import { Text } from 'merchant_common/views/Reports/components';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { VisibleRangeContainer } from './components/VisibleRangeContainer';

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
  validate = () => true,
  value,
  ariaLabel,
  modifiers,
}: DateTimeRangePickerPropsType): JSX.Element => {
  const { theme } = useTheme();
  const daySize = DAY_SIZE;
  return (
    <DateTimeRangePickerProvider value={value} onChange={onChange}>
      <DatesContextProvider>
        <CalendarField>
          {Boolean(label) && (
            <Text variant="body" type="normal" weight="bold">
              {label}
            </Text>
          )}
          <SelectedRangeInfoInput
            placeHolder={placeHolder}
            value={value}
            onChange={onChange}
            label={label}
            selectedRangeFormat={selectedRangeFormat}
            validate={validate}
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
          {Boolean(helpText) && (
            <Text variant="caption" type="subdued">
              {helpText}
            </Text>
          )}
        </CalendarField>
      </DatesContextProvider>
    </DateTimeRangePickerProvider>
  );
};

// pass onChange to this using a useCallback hook, otherwise it will rerender
// this picker
export const DateTimeRangePicker = React.memo(Picker);
