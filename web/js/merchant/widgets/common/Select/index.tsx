import React from 'react';
import {
  Dropdown,
  SelectInput,
  DropdownOverlay,
  ActionList,
  Box,
  ActionListItem,
} from '@razorpay/blade/components';
import { durationOptionsMap } from 'merchant/widgets/InsightsChart/utils';
import { SelectChangeEvent, SelectProps } from './types';
import { track } from 'merchant/widgets/utils';

function Select({ value, onChange, values, default_value, analyticsProperties }: SelectProps) {
  const handleChange = (e: SelectChangeEvent) => {
    onChange?.(e.values);
    const { screen, ...rest } = analyticsProperties;
    track({
      objectName: 'select',
      actionName: 'clicked',
      screen,
      properties: { ...rest, value: e.values[0] },
    });
  };

  return (
    <Box width="150px">
      <Dropdown selectionType="single" testID="date-picker-component">
        <SelectInput
          label=""
          accessibilityLabel="Date picker"
          placeholder="Duration"
          defaultValue={default_value}
          value={value}
          name="date"
          onChange={handleChange}
        />
        <DropdownOverlay>
          <ActionList>
            {values.map((value) => {
              return (
                <ActionListItem
                  title={durationOptionsMap[value]}
                  value={value}
                  key={`duration-${value}`}
                />
              );
            })}
          </ActionList>
        </DropdownOverlay>
      </Dropdown>
    </Box>
  );
}

export default Select;
