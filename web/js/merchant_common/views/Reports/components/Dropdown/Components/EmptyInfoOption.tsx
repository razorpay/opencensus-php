import React, { useContext } from 'react';
import { AsyncDropdownContext } from 'merchant_common/views/Reports/components';
import { Option } from './DropdownOption';

const EmptyOptionComponent = ({ children, itemHeight }) => {
  return (
    <Option<string, false>
      item={children}
      itemHeight={itemHeight}
      onClick={() => {}}
      disabled={true}
      value=""
    />
  );
};

export const EmptyOption = ({
  availableOptions,
  searchFor,
  isSearchable,
  itemHeight,
  shouldShowDropDown,
}): JSX.Element => {
  const { isAsyncDropdown, isAsyncLoading } = useContext(AsyncDropdownContext);

  const commonProps = { itemHeight };

  if (shouldShowDropDown && availableOptions.length === 0) {
    switch (true) {
      case Boolean(!isAsyncDropdown && isSearchable && searchFor.length):
        return <EmptyOptionComponent {...commonProps}>No results found</EmptyOptionComponent>;
      case Boolean(isSearchable && searchFor.length) && isAsyncDropdown && isAsyncLoading:
        return (
          <EmptyOptionComponent {...commonProps}>Loading... Please wait...</EmptyOptionComponent>
        );
      case isAsyncDropdown && searchFor.length === 0:
        return (
          <EmptyOptionComponent {...commonProps}>
            Start typing to load results.
          </EmptyOptionComponent>
        );
      default:
        return <EmptyOptionComponent {...commonProps}>No options to choose</EmptyOptionComponent>;
    }
  } else {
    return <></>;
  }
};
