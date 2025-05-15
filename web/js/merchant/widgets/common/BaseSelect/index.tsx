import {
  ActionList,
  ActionListItem,
  Dropdown,
  DropdownOverlay,
  DropdownProps,
  SelectInput,
  SelectInputProps,
} from '@razorpay/blade/components';
import React from 'react';

export type BaseSelectProps = {
  values: { label: string; value: string }[];
  dropdownProps?: DropdownProps;
} & SelectInputProps;

function BaseSelect(props: BaseSelectProps) {
  const { values, dropdownProps = {}, ...rest } = props;

  return (
    <Dropdown {...dropdownProps}>
      <SelectInput {...rest} />
      <DropdownOverlay>
        <ActionList>
          {values.map((option) => (
            <ActionListItem key={option.value} title={option.label} value={option.value} />
          ))}
        </ActionList>
      </DropdownOverlay>
    </Dropdown>
  );
}

export default BaseSelect;
