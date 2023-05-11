import React, { useRef, useState } from 'react';
import moment from 'moment';
import { AbsoluteWrapper } from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';
import {
  FlexCentered,
  FlexJustifyContentCenter,
} from 'merchant_common/views/Reports/components/styled';
import {
  SelectedMonthInfo,
  MonthContainer,
  MonthField,
  MonthInput,
  YearPickerStyled,
} from './styled';
import { Box, CalendarIcon, Heading, Text } from 'merchant_common/views/Reports/components';
import { MonthGrid } from 'merchant_common/views/Reports/components/DateTimeRangePicker/components';
import { useTheme, useClickOutSide } from 'merchant_common/views/Reports/hooks';
import { MonthIndex, MonthPickerProps } from './types';
import { FieldFooter } from 'merchant_common/views/Reports/components/FieldFooter';
import { FieldLabel } from 'merchant_common/views/Reports/components/FieldLabel';

export const MonthPicker = ({
  value,
  onChange,
  helpText,
  label,
  validate = () => true,
  placeHolder,
  necessityIndicator,
  errorText,
}: MonthPickerProps) => {
  const [isPickerOpen, setPickerOpen] = useState(false);
  const isValidated = validate();
  const pickerRef = useRef<HTMLDivElement>(null);
  const { theme } = useTheme();

  const handleMonthSelection = ([{ month }]: moment.MomentSetObject[]) => {
    onChange(month as MonthIndex);
    setPickerOpen(false);
  };

  useClickOutSide([pickerRef], () => {
    setPickerOpen(false);
  });

  return (
    <MonthField>
      <FieldLabel necessityIndicator={necessityIndicator} label={label} />
      <MonthInput ref={pickerRef} label={label} open={isPickerOpen}>
        <SelectedMonthInfo
          validation={isPickerOpen ? true : isValidated}
          aria-label="Selected Month Field"
          onClick={() => setPickerOpen(true)}
          focused={isPickerOpen}
        >
          <Text
            size="medium"
            type="normal"
            variant="body"
            color={
              typeof value === 'number'
                ? 'surface.text.normal.lowContrast'
                : 'surface.text.muted.lowContrast'
            }
          >
            {(typeof value === 'number' ? moment().month(value).format('MMMM') : null) ??
              placeHolder ??
              'Select A Month'}
          </Text>

          <CalendarIcon color="feedback.icon.neutral.lowContrast" size="medium" />
        </SelectedMonthInfo>
        {isPickerOpen ? (
          <AbsoluteWrapper topOffset={18}>
            <MonthContainer theme={theme} focused={true} validation={isValidated}>
              <YearPickerStyled>
                <FlexJustifyContentCenter>
                  <MonthGrid
                    daySize={36}
                    handleVisibleRange={(data) => handleMonthSelection(data)}
                    disableFuture={false}
                    disablePast={false}
                    setViewMode={() => {}}
                    isCompactView={true}
                    refDayMoment={moment()}
                    customCalendarHeading={() => (
                      <FlexCentered>
                        <Box marginBottom={'spacing.4'}>
                          <Heading size="small" weight="bold" type="subdued" variant="regular">
                            Select Month
                          </Heading>
                        </Box>
                      </FlexCentered>
                    )}
                  />
                </FlexJustifyContentCenter>
              </YearPickerStyled>
            </MonthContainer>
          </AbsoluteWrapper>
        ) : null}
      </MonthInput>
      <FieldFooter errorText={errorText} validation={isValidated} helpText={helpText} />
    </MonthField>
  );
};
