import React, { useState } from 'react';

import {
  ActionList,
  ActionListItem,
  Dropdown,
  DropdownOverlay,
  SelectInput,
} from 'merchant_common/views/Reports/components';
import {
  DELIMITER_ERROR_TEXT,
  DELIMITER_PLACEHOLDER,
  FORMATS_PLACEHOLDER,
} from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/components/Formats/constants';
import { Delimiter, Format } from 'merchant_common/views/Reports/types';
import { patchedSelectOnChange } from 'merchant_common/views/Reports/components/blade.patch';

import { FormatsProps } from './types';
import { getAvailableDelimiter } from './utils';

export function Formats({
  availableFormats,
  selectedFormat,
  selectedDelimiter,
  setSelectedFormat,
  setSelectedDelimiter,
  showErrorInSection,
}: FormatsProps): JSX.Element {
  const [availableDelimiters, setAvailableDelimiters] = useState<Delimiter[]>([]);

  const handleFormatSelect = ({ values }) => {
    const selectedIndex = Number(values[0]);
    const selectedFormat = availableFormats[selectedIndex];
    const availableDelimiters = getAvailableDelimiter(selectedFormat);

    setSelectedFormat(selectedFormat);
    setAvailableDelimiters(availableDelimiters);
    setSelectedDelimiter(availableDelimiters[0]);
  };

  const handleDelimiterSelect = ({ values }) => {
    const selectedIndex = Number(values[0]);

    setSelectedDelimiter(availableDelimiters[selectedIndex]);
  };

  return (
    <>
      <Dropdown selectionType="single">
        <SelectInput
          label="Select Format"
          name="selectedFormat"
          onChange={patchedSelectOnChange(handleFormatSelect)}
          placeholder={FORMATS_PLACEHOLDER}
          validationState="none"
          helpText="Select the format in which you want to receive the report in."
          necessityIndicator="optional"
        />
        <DropdownOverlay>
          <ActionList surfaceLevel={2}>
            {availableFormats.map(({ label, value }: Format, index: number) => {
              const isDefaultSelected = label === selectedFormat?.label;

              return (
                <ActionListItem
                  key={value}
                  title={label}
                  isDefaultSelected={isDefaultSelected}
                  value={index.toString()}
                  testID={label}
                />
              );
            })}
          </ActionList>
        </DropdownOverlay>
      </Dropdown>

      {availableDelimiters.length > 0 ? (
        <Dropdown selectionType="single" marginTop="spacing.3">
          <SelectInput
            label="Select Delimiter"
            name="selectedDelimiter"
            onChange={patchedSelectOnChange(handleDelimiterSelect)}
            placeholder={DELIMITER_PLACEHOLDER}
            validationState={
              showErrorInSection === 0 && !Boolean(selectedDelimiter) ? 'error' : 'none'
            }
            helpText="Select the delimiter in which you want to receive the report in."
            necessityIndicator="required"
            isDisabled={availableDelimiters.length === 1}
            errorText={DELIMITER_ERROR_TEXT}
            testID="delimiterInput"
          />
          <DropdownOverlay>
            {/* @blade-patch -> "key" */}
            <ActionList surfaceLevel={2} key={selectedDelimiter?.label}>
              {availableDelimiters.map(({ label }: Delimiter, index: number) => {
                const isDefaultSelected = label === selectedDelimiter?.label;

                return (
                  <ActionListItem
                    key={label}
                    title={label}
                    isDefaultSelected={isDefaultSelected}
                    value={index.toString()}
                    testID={label}
                  />
                );
              })}
            </ActionList>
          </DropdownOverlay>
        </Dropdown>
      ) : null}
    </>
  );
}
