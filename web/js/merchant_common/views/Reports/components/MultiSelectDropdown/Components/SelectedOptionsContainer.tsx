import React, { Fragment } from 'react';
import {
  SelectedOption,
  SelectionOptionsContainer,
} from 'merchant_common/views/Reports/components/MultiSelectDropdown/styled';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { Text, IconButton, CloseIcon } from 'merchant_common/views/Reports/components';
import { SingleOrMulipleItem } from 'merchant_common/views/Reports/components/types';

export const SelectedOptionsContainer = <ItemType, AllowMultiple>({ value, refKey, onChange }) => {
  const { theme } = useTheme();

  const handleOptionDeselection = (refOption) => {
    if (value && Array.isArray(value)) {
      const copySelectedItems = [...value];
      copySelectedItems.splice(
        copySelectedItems.findIndex((d) =>
          typeof d === 'string' ? d === refOption : d[refKey] === refOption[refKey],
        ),
        1,
      );
      onChange(copySelectedItems as SingleOrMulipleItem<ItemType, AllowMultiple>);
    }
  };

  return (
    <SelectionOptionsContainer aria-label="Selected Option Container">
      {value.map((selectedOption) => {
        const val =
          typeof selectedOption === 'string'
            ? selectedOption
            : refKey
            ? selectedOption[refKey]
            : null;
        if (!val) return <Fragment />;
        return (
          <SelectedOption theme={theme} key={val}>
            <Text size="medium" type="normal" variant="body">{`${val} `}</Text>
            <span
              style={{
                marginTop: 2,
              }}
              onMouseDown={(mouseEvent) => mouseEvent.preventDefault()}
            >
              <IconButton
                icon={CloseIcon}
                accessibilityLabel={`Remove Selected Option -> ${val}`}
                onClick={() => handleOptionDeselection(selectedOption)}
              />
            </span>
          </SelectedOption>
        );
      })}
    </SelectionOptionsContainer>
  );
};
