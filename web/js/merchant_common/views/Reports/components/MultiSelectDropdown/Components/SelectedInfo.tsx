import React from 'react';
import { SingleSelectedOption } from 'merchant_common/views/Reports/components/MultiSelectDropdown/styled';
import { Text } from 'merchant_common/views/Reports/components';
import { useTheme } from 'merchant_common/views/Reports/hooks';

export const DropdownSingleSelectedInput = ({
  value,
  labelKey,
  placeHolder,
  shouldShowDropDown,
}) => {
  const { theme } = useTheme();
  return (
    <SingleSelectedOption shouldShowDropDown={shouldShowDropDown} theme={theme}>
      <Text
        size="medium"
        type="normal"
        variant="body"
        truncateAfterLines={1}
        color={
          value && !shouldShowDropDown
            ? 'surface.text.subtle.lowContrast'
            : 'surface.text.muted.lowContrast'
        }
      >
        {value && !shouldShowDropDown
          ? typeof value === 'string'
            ? value
            : typeof labelKey === 'string'
            ? value[labelKey]
            : null
          : placeHolder ?? 'Select An Option'}
      </Text>
    </SingleSelectedOption>
  );
};
