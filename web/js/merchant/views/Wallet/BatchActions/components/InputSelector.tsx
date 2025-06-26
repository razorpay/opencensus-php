import React, { Dispatch, SetStateAction, useCallback } from 'react';
import {
  ActionList,
  ActionListItem,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  Box,
} from '@razorpay/blade/components';

export interface InputSelectorProps {
  setInput: Dispatch<SetStateAction<string>>;
  options: Array<{ label: string; name: string }>;
  defaultValue?: string;
}

const InputSelector = ({ setInput, options, defaultValue }: InputSelectorProps): JSX.Element => {
  const setField = useCallback(
    (e) => {
      setInput(e.values?.[0]);
    },
    [setInput],
  );

  return (
    <Box padding={['spacing.5', 'spacing.7', '0px', 'spacing.7']}>
      <Dropdown selectionType="single">
        <SelectInput
          label="Load Type"
          labelPosition="top"
          name="action"
          onChange={setField}
          placeholder="Select Option"
          validationState="none"
          isRequired={true}
          testID="test-load-dropdown"
          defaultValue={defaultValue || 'accounts'}
        />
        <DropdownOverlay>
          <ActionList>
            {options?.map((type) => (
              <ActionListItem key={type.name} title={type.label} value={type.name} />
            ))}
          </ActionList>
        </DropdownOverlay>
      </Dropdown>
    </Box>
  );
};

export default InputSelector;
