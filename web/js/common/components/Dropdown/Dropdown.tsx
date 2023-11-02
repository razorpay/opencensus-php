import React, { useRef, useState } from 'react';
import {
  Dropdown as BladeDropdown,
  ChevronUpIcon,
  ChevronDownIcon,
  VisuallyHidden,
} from '@razorpay/blade/components';
import { DropdownProps, Option, ClickProps, DropdownCommonProps } from './types';
import { useMobile } from 'common/hooks/useMobile';
import { getAllOptions, getDropdownContent, getDropdownTarget } from './utils';

const Dropdown = ({
  prefixTitle = '',
  defaultOptions = [],
  options,
  onChange,
  isLink = false,
  isSelectInput = false,
  isDisabled = false,
  selectionType,
  selectInputName = '',
  withBottomSheet,
  bottomSheetTitle = '',
  testID,
}: DropdownProps): JSX.Element => {
  const isMobile = useMobile();
  const isWithBottomSheet = withBottomSheet === undefined ? isMobile : withBottomSheet;
  const isMultipleSelection = selectionType === 'multiple';
  const [selectedOptions, setSelectedOptions] = useState(defaultOptions);
  const [tempSelectedOptions, setTempSelectedOptions] = useState(defaultOptions);
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);
  const allOptions = useRef<Option[]>(getAllOptions(options));
  const closeButtonRef = useRef<HTMLButtonElement>(null);
  const hideDropDown = () => setIsDropdownOpen(false);
  const toggleDropDown = () => setIsDropdownOpen(!isDropdownOpen);
  const onDismiss = () => {
    if (isMultipleSelection) {
      setTempSelectedOptions(selectedOptions);
    }
    hideDropDown();
  };

  const onOptionClick =
    (clickedOption: Option) =>
    ({ name, value }: ClickProps) => {
      let newOptions: Option[] = [clickedOption];
      if (isMultipleSelection) {
        // value is true which means it is selected. Then we deselect it.
        if (value) {
          const isAllDeselected = clickedOption.value === 'all';
          newOptions = isAllDeselected
            ? []
            : tempSelectedOptions.filter(
                ({ value: selectedValue }) => ![name, 'all'].includes(selectedValue),
              );
        } else {
          const isAllOptionsSelected = () =>
            //  Note: allOptionsLength - allOption === selectedOptionsLength + nextSelectedOption
            allOptions.current.length - 1 === tempSelectedOptions.length + 1;
          const getSelectedOptions = () => {
            const newSelectedOptions = [...tempSelectedOptions, clickedOption];
            const newOptions = allOptions.current.filter(({ value: optionValue }) =>
              newSelectedOptions.some(({ value: selectedValue }) => selectedValue === optionValue),
            );
            return newOptions;
          };
          const isAllSelected = clickedOption.value === 'all' || isAllOptionsSelected();
          newOptions = isAllSelected ? [...allOptions.current] : getSelectedOptions();
        }
        setTempSelectedOptions(newOptions);
      } else {
        onChange?.(newOptions);
        setSelectedOptions(newOptions);
      }
    };

  const onClear = () => {
    setTempSelectedOptions([]);
  };

  const onApply = () => {
    //TODO: close multiple select BottomSheet when apply button is clicked
    setSelectedOptions(tempSelectedOptions);
    onChange?.(tempSelectedOptions);
    closeButtonRef.current?.focus();
  };

  const dropdownCommonProps: DropdownCommonProps = {
    icon: isDropdownOpen ? ChevronUpIcon : ChevronDownIcon,
    iconPosition: 'right' as const,
    onClick: toggleDropDown,
    'data-testid': 'dropdown',
    isDisabled,
  };
  let dropdownTitle = prefixTitle + (selectedOptions[0]?.title || 'None');
  if (selectedOptions.length > 1 && selectedOptions.length !== allOptions.current.length) {
    dropdownTitle = `${dropdownTitle} +${selectedOptions.length - 1}`;
  }

  return (
    <div>
      {/* hack to close the DropdownOverlay when clicking on Apply button */}
      {isMultipleSelection ? (
        <VisuallyHidden>
          <button ref={closeButtonRef}>Close</button>
        </VisuallyHidden>
      ) : null}
      <BladeDropdown onDismiss={onDismiss} selectionType={selectionType} testID={testID}>
        {getDropdownTarget({
          isLink,
          isSelectInput,
          selectInputName,
          dropdownCommonProps,
          dropdownTitle,
          defaultOptions,
        })}
        {getDropdownContent({
          isMultipleSelection,
          selectedOptions,
          tempSelectedOptions,
          onOptionClick,
          onClear,
          onApply,
          options,
          isWithBottomSheet,
          bottomSheetTitle,
          isDropdownOpen,
        })}
      </BladeDropdown>
    </div>
  );
};

export default Dropdown;
