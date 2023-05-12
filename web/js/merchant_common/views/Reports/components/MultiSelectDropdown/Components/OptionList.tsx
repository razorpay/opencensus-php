import React from 'react';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import {
  AbsoluteWrapper,
  DropdownItemList,
  NonVirtualList,
} from 'merchant_common/views/Reports/components/MultiSelectDropdown/styled';
import List from 'rc-virtual-list';
import { Option, OptionForwardedRef } from './DropdownOption';
import { SingleOrMultipleItem } from 'merchant_common/views/Reports/components/types';

export const OptionList = <ItemType, AllowMultiple>({
  isValidated,
  itemHeight,
  availableOptions,
  visibleOptionsCount,
  refKey,
  renderCustomOption,
  value,
  emptyOption,
  isVirtualized,
  shouldAllowMultiple,
  onChange,
  setSearchFor,
  onSearchInput,
  shouldCloseDropdownOnSelect,
  handleDropdownClose,
}) => {
  const { theme } = useTheme();

  const onOptionSelect = (item: ItemType) => {
    if (shouldAllowMultiple) {
      if (value && Array.isArray(value)) {
        onChange([...value, item] as SingleOrMultipleItem<ItemType, AllowMultiple>);
      } else {
        // just a fail safe
        onChange([item] as SingleOrMultipleItem<ItemType, AllowMultiple>);
      }
      setSearchFor('');
      if (typeof onSearchInput === 'function') onSearchInput('');
    } else {
      onChange(item as SingleOrMultipleItem<ItemType, AllowMultiple>);
    }
    if (shouldCloseDropdownOnSelect) {
      handleDropdownClose();
    }
  };

  return (
    <AbsoluteWrapper onClick={(e) => e.stopPropagation()}>
      <DropdownItemList
        aria-label="dropdown option container"
        theme={theme}
        validation={isValidated}
      >
        {emptyOption()}
        {isVirtualized && itemHeight && typeof itemHeight === 'number' ? (
          <List
            id="list"
            data={availableOptions}
            height={visibleOptionsCount * itemHeight}
            itemHeight={itemHeight}
            itemKey={refKey ? refKey : 'id'}
          >
            {(item) => (
              <OptionForwardedRef<ItemType, AllowMultiple>
                item={item}
                itemHeight={itemHeight}
                renderCustomOption={renderCustomOption}
                onClick={onOptionSelect}
                labelKey={refKey}
                value={value}
              />
            )}
          </List>
        ) : (
          <NonVirtualList scrollbarColor="rgba(0, 0, 0, 0.5)">
            {availableOptions.map((item, key) => {
              return (
                <Option<ItemType, AllowMultiple>
                  key={key}
                  item={item}
                  renderCustomOption={renderCustomOption}
                  onClick={onOptionSelect}
                  labelKey={refKey}
                  value={value}
                />
              );
            })}
          </NonVirtualList>
        )}
      </DropdownItemList>
    </AbsoluteWrapper>
  );
};
