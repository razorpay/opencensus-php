import React, { useState } from 'react';
import {
  Box,
  Checkbox,
  CheckboxGroup,
  Collapsible,
  CollapsibleBody,
  CollapsibleLink,
  Text,
  useTheme,
} from '@razorpay/blade/components';

import type { MultiSelectSlotProps } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/StoreFilter/types';

const MultiSelectSlot = ({
  name,
  options = [],
  onChange,
  title,
  minExpandedElements = 3,
}: MultiSelectSlotProps): React.ReactElement => {
  const [slotValues, setSlotValues] = useState<string[]>([]);
  const [isOptionsExpanded, setIsOptionsExpanded] = useState<boolean>(false);
  const bladeTheme = useTheme();

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
      {options.length === 0 && <Text size="small">No {title} found</Text>}
      {/* Collapsible adds a margin top of 16px */}
      <Box marginBottom={isOptionsExpanded ? `-${bladeTheme.theme.spacing[5]}px` : 'spacing.0'}>
        {options.slice(0, minExpandedElements).map((option) => (
          <Checkbox key={option.value} value={option.value} marginBottom="spacing.4">
            {option.label}
          </Checkbox>
        ))}
      </Box>
      {options.length > minExpandedElements ? (
        <Collapsible
          marginTop="spacing.0"
          isExpanded={isOptionsExpanded}
          onExpandChange={({ isExpanded }): void => {
            setIsOptionsExpanded(isExpanded);
          }}
        >
          <CollapsibleBody>
            {options.slice(minExpandedElements).map((option) => (
              <Checkbox key={option.value} value={option.value} marginBottom="spacing.4">
                {option.label}
              </Checkbox>
            ))}
          </CollapsibleBody>
          <CollapsibleLink size="small">View All</CollapsibleLink>
        </Collapsible>
      ) : null}
    </CheckboxGroup>
  );
};

export default MultiSelectSlot;
