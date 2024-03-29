import React from 'react';
import { SingleSelectedOption } from 'merchant_common/views/Reports/components/MultiSelectDropdown/styled';
import { Text } from 'merchant_common/views/Reports/components';
import { useTheme } from 'merchant_common/views/Reports/hooks';

export const DropdownSingleSelectedInput = ({
  value,
  labelKey,
  placeHolder,
  shouldShowDropDown,
  shouldAllowMultiple,
}): JSX.Element => {
  const { theme } = useTheme();

  const renderDropdownText = () => {
    if (value && !shouldShowDropDown && !shouldAllowMultiple) {
      if (typeof value === 'string') {
        return value;
      } else if (typeof labelKey === 'string') {
        return value[labelKey];
      } else {
        return '';
      }
    } else if (placeHolder) {
      return placeHolder;
    }
    return 'Select An Option';
  };

  return (
    <SingleSelectedOption shouldShowDropDown={shouldShowDropDown} theme={theme}>
      <Text
        size="medium"
        variant="body"
        truncateAfterLines={1}
        color={
          value && !shouldShowDropDown ? 'surface.text.gray.subtle' : 'surface.text.gray.muted'
        }
      >
        {renderDropdownText()}
      </Text>
    </SingleSelectedOption>
  );
};
