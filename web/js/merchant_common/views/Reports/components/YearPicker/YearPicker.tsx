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
import { Text } from 'merchant_common/views/Reports/components';
import { YearGrid } from 'merchant_common/views/Reports/components/DateTimeRangePicker/components';
import { useTheme, useClickOutSide } from 'merchant_common/views/Reports/hooks';
import { YearPickerProps } from './types';

export const YearPicker = ({
  value,
  onChange,
  disableFuture = false,
  disablePast = false,
  helpText,
  label,
  validate = () => true,
  placeHolder,
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
      {Boolean(label) && (
        <Text variant="body" type="normal" weight="bold">
          {label}
        </Text>
      )}

      <YearInput ref={pickerRef} label={label} open={isPickerOpen}>
        <SelectedYearInfo
          validation={isPickerOpen ? true : isValidated}
          aria-label="Selected Year Field"
          onClick={() => setPickerOpen(true)}
        >
          <Text
            variant="body"
            type="normal"
            weight="regular"
            color={
              isValidated ? 'surface.text.subtle.lowContrast' : 'surface.text.muted.lowContrast'
            }
          >
            {value ?? placeHolder ?? 'Select A Year'}
          </Text>
        </SelectedYearInfo>
        {isPickerOpen ? (
          <AbsoluteWrapper>
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
                />
              </YearPickerStyled>
            </YearContainer>
          </AbsoluteWrapper>
        ) : null}
      </YearInput>

      {Boolean(helpText) && (
        <Text
          variant="caption"
          type="subdued"
          weight="regular"
          color={
            isValidated ? 'surface.text.subdued.lowContrast' : 'feedback.text.negative.lowContrast'
          }
        >
          {isValidated ? helpText : `Mandatory Field: ${helpText}`}
        </Text>
      )}
    </YearField>
  );
};
