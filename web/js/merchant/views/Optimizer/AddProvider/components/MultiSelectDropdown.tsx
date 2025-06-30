import React from 'react';
import {
  Box,
  Text,
  Dropdown,
  DropdownOverlay,
  AutoComplete,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';

import { WalletLabels } from 'merchant/views/Navigator/components/util';
import { PAYLATER_LABELS } from 'merchant/views/Optimizer/AddProvider/constants';

interface MultiSelectDropdownProps {
  label: string;
  options: string[];
  selectedOptions?: string[];
  changeOptions: ({ name, values }: { name?: string, values: string[] }) => void;
  disabled: boolean;
  isFormEdit: boolean;
}

const MultiSelectDropdown = (props: MultiSelectDropdownProps): JSX.Element => {
  const { label, options, selectedOptions, changeOptions, disabled, isFormEdit } = props;

  let labelNames;
  if (label === 'Wallets') {
    labelNames = WalletLabels;
  }else if (label === 'Paylaters') {
    labelNames = PAYLATER_LABELS;
  }

  // Check if all individual options are selected
  const allIndividualOptionsSelected = options.length > 0 && options.every(option => selectedOptions?.includes(option));

  // Create the value for AutoComplete - include "all_values" when all options are selected
  const autoCompleteValue = allIndividualOptionsSelected 
    ? [...(selectedOptions || []), 'all_values']
    : selectedOptions || [];

  // Enhanced changeOptions that handles "Select All" logic
  const handleChangeOptions = ({ name, values }: { name?: string, values: string[] }) => {
    const allValuesInSelection = values.includes('all_values');
    const allValuesWasPreviouslySelected = allIndividualOptionsSelected;
    
    // If "all_values" was just selected (and wasn't selected before)
    if (allValuesInSelection && !allValuesWasPreviouslySelected) {
      // Select all individual options
      changeOptions({ name, values: [...options] });
    }
    // If "all_values" was just deselected (and was selected before)
    else if (!allValuesInSelection && allValuesWasPreviouslySelected) {
      // Deselect all options
      changeOptions({ name, values: [] });
    }
    // If individual options were selected/deselected
    else {
      // Filter out "all_values" from the selection and pass only individual options
      const filteredValues = values.filter(value => value !== 'all_values');
      changeOptions({ name, values: filteredValues });
    }
  };

  return (
    <Box display="flex" alignItems="center">
      <Box minWidth="180px">
        <Text>{label}</Text>
      </Box>
      {isFormEdit ? (
        <Box minWidth="280px">
          <Dropdown selectionType="multiple" testID="option-select">
            <AutoComplete
              label=''
              placeholder={`Select ${label}`}
              name={label}
              value={autoCompleteValue}
              isDisabled={disabled}
              onChange={handleChangeOptions}
            />
            <DropdownOverlay>
              <ActionList>
                <ActionListItem
                  key="all_values"
                  title="Select All"
                  value="all_values"
                  isSelected={allIndividualOptionsSelected}
                />
                {options.map((option) => (
                  <ActionListItem
                    key={option}
                    title={labelNames?.[option] ?? option}
                    value={option}
                  />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
      ) : (
        <Text weight="semibold">
          {selectedOptions?.map((option) => labelNames?.[option] ?? option)?.join(', ')}
        </Text>
      )}
    </Box>
  );
};

export default MultiSelectDropdown;
