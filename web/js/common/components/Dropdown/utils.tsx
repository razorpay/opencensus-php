import React from 'react';
import {
  DropdownLink,
  DropdownButton,
  DropdownOverlay,
  DropdownFooter,
  SelectInput,
  BottomSheet,
  BottomSheetBody,
  BottomSheetHeader,
  BottomSheetFooter,
} from '@razorpay/blade/components';

import ActionListWrapper from './components/ActionListWrapper/ActionListWrapper';
import FooterActions from './components/FooterActions/FooterActions';
import { SectionOption, Option, Options, DropdownTarget, DropdownContent } from './types';

export const getAllOptions = (options: Options): Option[] => {
  const allOptions: Option[] = [];
  options.forEach((option) => {
    if ((option as SectionOption).section) {
      allOptions.push(...(option as SectionOption).section.options);
    } else {
      allOptions.push(option as Option);
    }
  });
  return allOptions;
};

// can't use component here as Dropdown target rejects to accept any other component type
//  except SelectInput or DropdownButton
export const getDropdownTarget = ({
  isLink,
  isSelectInput,
  selectInputName,
  dropdownCommonProps,
  dropdownTitle,
  defaultOptions,
}: DropdownTarget): JSX.Element => {
  const { isDisabled } = dropdownCommonProps;
  if (isLink) {
    return <DropdownLink {...dropdownCommonProps}>{dropdownTitle} </DropdownLink>;
  }
  if (isSelectInput) {
    const defaultValue = defaultOptions.map(({ value }) => value);
    return (
      <SelectInput
        label=""
        defaultValue={defaultValue}
        name={selectInputName}
        isDisabled={isDisabled}
      />
    );
  }
  return (
    <DropdownButton {...dropdownCommonProps} variant="tertiary">
      {dropdownTitle}
    </DropdownButton>
  );
};

// can't use component here as Dropdown content rejects to accept any other component type
// except BottomSheet or DropdownOverlay
const defaultSnapPoints: [number, number, number] = [0.5, 0.7, 0.85];
export const getDropdownContent = ({
  isMultipleSelection,
  selectedOptions,
  tempSelectedOptions,
  onOptionClick,
  onClear,
  onApply,
  options,
  isWithBottomSheet,
  bottomSheetTitle,
}: DropdownContent): JSX.Element => {
  if (isWithBottomSheet) {
    return (
      <BottomSheet snapPoints={defaultSnapPoints}>
        {bottomSheetTitle && <BottomSheetHeader title={bottomSheetTitle} />}
        <BottomSheetBody>
          <ActionListWrapper
            isMultipleSelection={isMultipleSelection}
            selectedOptions={selectedOptions}
            tempSelectedOptions={tempSelectedOptions}
            onOptionClick={onOptionClick}
            options={options}
          />
          {isMultipleSelection && (
            <BottomSheetFooter>
              <FooterActions
                tempSelectedOptions={tempSelectedOptions}
                onClear={onClear}
                onApply={onApply}
              />
            </BottomSheetFooter>
          )}
        </BottomSheetBody>
      </BottomSheet>
    );
  }
  return (
    <DropdownOverlay>
      <ActionListWrapper
        isMultipleSelection={isMultipleSelection}
        selectedOptions={selectedOptions}
        tempSelectedOptions={tempSelectedOptions}
        onOptionClick={onOptionClick}
        options={options}
      />
      {isMultipleSelection ? (
        <DropdownFooter>
          <FooterActions
            tempSelectedOptions={tempSelectedOptions}
            onClear={onClear}
            onApply={onApply}
          />
        </DropdownFooter>
      ) : (
        <div />
      )}
    </DropdownOverlay>
  );
};
