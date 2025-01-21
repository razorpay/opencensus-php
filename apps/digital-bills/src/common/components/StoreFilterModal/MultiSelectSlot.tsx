import React, { useState } from 'react';
import {
  Box,
  Checkbox,
  CheckboxGroup,
  Collapsible,
  CollapsibleBody,
  CollapsibleLink,
  CheckboxGroupProps,
  Text,
} from '@razorpay/blade/components';

import type { SlotOptionType } from '@apps/digital-bills/src/common/components/StoreFilterModal/types';

type MultiSelectSlotProps = {
  title: string;
  options: SlotOptionType[];
  onChange?: CheckboxGroupProps['onChange'];
  minExpandedElements?: number;
  name: string;
};

const MultiSelectSlot = ({
  name,
  options = [],
  onChange,
  title,
  minExpandedElements = 3,
}: MultiSelectSlotProps): React.ReactElement => {
  const [slotValues, setSlotValues] = useState<string[]>([]);
  const [isOptionsExpanded, setIsOptionsExpanded] = useState<boolean>(false);

  const filteredOptions = options.filter((option) => option.value);
  return (
    <CheckboxGroup
      value={slotValues}
      name={name}
      label={title}
      onChange={({ values }): void => {
        setSlotValues(values);
        onChange?.({ name, values });
      }}
    >
      {filteredOptions.length === 0 ? <Text size="small">No {title} found</Text> : null}
      {/* Collapsible adds a margin top of 16px */}
      <Box marginBottom={isOptionsExpanded ? '-16px' : 'spacing.0'}>
        {filteredOptions.slice(0, minExpandedElements).map((option) => {
          return (
            <Checkbox key={option.value} value={option.value} marginBottom="spacing.4">
              {option.label}
            </Checkbox>
          );
        })}
      </Box>
      {filteredOptions.length > minExpandedElements ? (
        <Collapsible
          marginTop="spacing.0"
          isExpanded={isOptionsExpanded}
          onExpandChange={({ isExpanded }): void => {
            setIsOptionsExpanded(isExpanded);
          }}
        >
          <CollapsibleBody>
            {filteredOptions.slice(minExpandedElements).map((option) => {
              return (
                <Checkbox key={option.value} value={option.value} marginBottom="spacing.4">
                  {option.label}
                </Checkbox>
              );
            })}
          </CollapsibleBody>
          <CollapsibleLink size="small">View All</CollapsibleLink>
        </Collapsible>
      ) : null}
    </CheckboxGroup>
  );
};

export default MultiSelectSlot;
