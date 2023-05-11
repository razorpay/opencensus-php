import React from 'react';
import { BaseDropdown } from './_BaseDropdown';
import { BaseDropdownPropsType } from './types';

export const MultiSelectDropdown = <ItemType, Virtualized>(
  props: Omit<BaseDropdownPropsType<ItemType, true, Virtualized>, 'shouldAllowMultiple'>,
): JSX.Element => {
  const dropdownProps = {
    ...props,
    shouldAllowMultiple: true,
  } as unknown as BaseDropdownPropsType<Record<string, unknown>, true, true>;
  return <BaseDropdown {...dropdownProps} />;
};
