import React from 'react';
import { Text } from 'merchant_common/views/Reports/components';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import {
  BaseOption,
  DefaultOption,
} from 'merchant_common/views/Reports/components/MultiSelectDropdown/styled';
import { OptionPropsType } from 'merchant_common/views/Reports/components/MultiSelectDropdown/types';

export const Option = <ItemType, AllowMultiple>(
  {
    item,
    onClick,
    itemHeight,
    renderCustomOption,
    labelKey,
    disabled = false,
    value,
  }: OptionPropsType<ItemType, AllowMultiple>,
  key,
) => {
  const { theme } = useTheme();

  const isSelectedOption =
    value &&
    (typeof value === 'string' ? value === item : labelKey && value[labelKey] === item[labelKey]);

  const virtualListStyle = {
    style: {
      height: itemHeight,
      paddingLeft: 8,
      borderRadius: 4,
    },
  };

  const noVirtualListStyle = {
    style: {
      padding: '8px 12px',
    },
  };

  const commonProps = {
    ...(itemHeight ? virtualListStyle : noVirtualListStyle),
    onClick: () => onClick(item),
    onMouseDown: (mouseDownEvent) => mouseDownEvent.preventDefault(),
  };

  const label = typeof item === 'string' ? item : labelKey && item?.[labelKey];

  return (
    <BaseOption
      role="option"
      aria-label={label}
      key={key}
      disabled={disabled}
      theme={theme}
      selected={Boolean(isSelectedOption)}
      {...commonProps}
    >
      {renderCustomOption ? (
        renderCustomOption({
          ...item,
        })
      ) : (
        <DefaultOption>
          <Text
            size="medium"
            type="normal"
            contrast="low"
            color={disabled ? 'surface.text.muted.lowContrast' : 'surface.text.normal.lowContrast'}
            truncateAfterLines={1}
          >
            {label}
          </Text>
        </DefaultOption>
      )}
    </BaseOption>
  );
};

export const OptionForwardedRef = React.forwardRef(Option) as <ItemType, AllowMultiple>(
  x: OptionPropsType<ItemType, AllowMultiple>,
  key: any,
) => JSX.Element;
