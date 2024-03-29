import React, { useRef, useState } from 'react';
import moment from 'moment';
import { AbsoluteWrapper } from 'merchant_common/views/Reports/components/DateTimeRangePicker/styled';
import { PickerNavigation } from 'merchant_common/views/Reports/components/DateTimeRangePicker/components/PickerNavigation';
import {
  PickerNavigationContainer,
  SelectedYearInfo,
  YearContainer,
  YearField,
  YearInput,
  YearPickerStyled,
} from './styled';
import { CalendarIcon, Text, Box } from 'merchant_common/views/Reports/components';
import { YearGrid } from 'merchant_common/views/Reports/components/DateTimeRangePicker/components';
import { useTheme, useClickOutSide } from 'merchant_common/views/Reports/hooks';
import { YearPickerProps } from './types';
import { FlexCentered } from 'merchant_common/views/Reports/components/styled';
import { FieldFooter } from 'merchant_common/views/Reports/components/FieldFooter';
import { FieldLabel } from 'merchant_common/views/Reports/components/FieldLabel';

export const YearPicker = ({
  value,
  onChange,
  disableFuture = false,
  disablePast = false,
  helpText,
  label,
  validate = () => true,
  placeHolder,
  necessityIndicator,
  errorText,
}: YearPickerProps) => {
  const [isPickerOpen, setPickerOpen] = useState(false);
  const [yearRangeIndex, setYearIndex] = useState(0);
  const isValidated = validate();
  const pickerRef = useRef<HTMLDivElement>(null);
  const { theme } = useTheme();

  const handlePrevClick = () => {
    setYearIndex(yearRangeIndex - 1);
  };
  const handleNextClick = () => {
    setYearIndex(yearRangeIndex + 1);
  };

  const handleYearSelection = ([{ year }]: moment.MomentSetObject[]) => {
    if (year) {
      onChange(year);
    }
    setPickerOpen(false);
  };

  useClickOutSide([pickerRef], () => {
    setPickerOpen(false);
  });

  const isNextClickAllowed = yearRangeIndex <= 2 && (disableFuture ? yearRangeIndex < 0 : true);

  const isPrevClickAllowed = yearRangeIndex >= -2 && (disablePast ? yearRangeIndex > 0 : true);

  return (
    <YearField>
      <FieldLabel necessityIndicator={necessityIndicator} label={label} />
      <YearInput ref={pickerRef} label={label} open={isPickerOpen}>
        <SelectedYearInfo
          validation={isPickerOpen ? true : isValidated}
          aria-label="Selected Year Field"
          onClick={() => setPickerOpen(true)}
          focused={isPickerOpen}
        >
          <Text
            size="medium"
            variant="body"
            color={
              typeof value === 'number' ? 'surface.text.gray.normal' : 'surface.text.gray.muted'
            }
          >
            {value ?? placeHolder ?? 'Select A Year'}
          </Text>
          <CalendarIcon color="feedback.icon.neutral.intense" size="medium" />
        </SelectedYearInfo>
        {isPickerOpen ? (
          <AbsoluteWrapper topOffset={18}>
            <YearContainer theme={theme} focused={true} validation={isValidated}>
              <YearPickerStyled>
                <PickerNavigationContainer>
                  <PickerNavigation
                    isNextClickAllowed={isNextClickAllowed}
                    isPrevClickAllowed={isPrevClickAllowed}
                    onNextClick={handleNextClick}
                    onPrevClick={handlePrevClick}
                    viewMode="year"
                  />
                </PickerNavigationContainer>

                <YearGrid
                  daySize={36}
                  handleVisibleRange={(data) => handleYearSelection(data)}
                  disableFuture={disableFuture}
                  setVisibleYearsRangeIndex={setYearIndex}
                  visibleYearsRangeIndex={yearRangeIndex}
                  disablePast={disablePast}
                  setViewMode={() => {}}
                  isCompactView={true}
                  refDayMoment={moment()}
                  customCalendarHeading={() => (
                    <FlexCentered>
                      <Box marginBottom={'spacing.4'}>
                        <Text weight="semibold" size="large" color="surface.text.gray.muted">
                          Select Year
                        </Text>
                      </Box>
                    </FlexCentered>
                  )}
                />
              </YearPickerStyled>
            </YearContainer>
          </AbsoluteWrapper>
        ) : null}
      </YearInput>
      <FieldFooter errorText={errorText} validation={isValidated} helpText={helpText} />
    </YearField>
  );
};
