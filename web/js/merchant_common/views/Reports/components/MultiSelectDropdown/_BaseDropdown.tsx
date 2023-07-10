import React, { useEffect, useRef, useState, useContext } from 'react';
import {
  ChevronDownIcon,
  Spinner,
  AsyncDropdownContext,
  ChevronUpIcon,
} from 'merchant_common/views/Reports/components';
import { useTheme, useClickOutSide } from 'merchant_common/views/Reports/hooks';
import {
  DropdownIconWrapper,
  InfoPanel,
  InputContainer,
  MultiSelectQueryInput,
  SelectContainer,
} from './styled';
import { BaseDropdownPropsType } from './types';
import { useDropdownOptions } from './hooks/useDropdownOptions';
import { DropdownSingleSelectedInput } from './Components/SelectedInfo';
import { EmptyOption } from './Components/EmptyInfoOption';
import { OptionList } from './Components/OptionList';
import { SelectedOptionsContainer } from './Components/SelectedOptionsContainer';
import { FieldFooter } from 'merchant_common/views/Reports/components/FieldFooter';
import { FieldLabel } from 'merchant_common/views/Reports/components/FieldLabel';

export const BaseDropdown = <ItemType, AllowMultiple, Virtualized>(
  props: BaseDropdownPropsType<ItemType, AllowMultiple, Virtualized>,
): JSX.Element => {
  const {
    shouldAllowMultiple,
    ariaLabelBy,
    shouldCloseDropdownOnSelect = true,
    defaultValue,
    helpText,
    itemHeight = 36,
    label = '',
    labelKey,
    isLoading = false,
    maxVisibleOption = 5,
    onChange,
    onSearchInput,
    options = [],
    placeHolder,
    renderCustomOption,
    isSearchable = false,
    validate = () => true,
    value,
    isVirtualized,
    tabIndex,
    necessityIndicator,
    errorText,
    isDisabled = false,
    // as in case of components full potential
  } = props as unknown as BaseDropdownPropsType<Record<string, unknown>, true, true>;

  const { theme } = useTheme();
  const [searchFor, setSearchFor] = useState('');
  const [shouldShowDropDown, setShowDropDown] = useState(false);
  const isValidated = validate();
  const refKey = typeof labelKey === 'string' ? labelKey : undefined;

  const { isAsyncDropdown, setOptionsCallback, isAsyncLoading, setAsyncLoading } =
    useContext(AsyncDropdownContext);

  const ref = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  const shouldShowInput = isSearchable && (shouldAllowMultiple ? true : shouldShowDropDown);

  const availableOptions = useDropdownOptions(
    options,
    value,
    labelKey,
    searchFor,
    shouldAllowMultiple,
    shouldShowInput,
  );

  const visibleOptionsCount =
    typeof maxVisibleOption === 'number' &&
    (maxVisibleOption > availableOptions.length ? availableOptions.length : maxVisibleOption);

  const handleDropdownClose = () => {
    setSearchFor('');
    if (typeof onSearchInput === 'function') onSearchInput('');
    setShowDropDown(false);
    if (isAsyncDropdown) {
      setAsyncLoading(false);
      setOptionsCallback([]);
    }
  };

  const onSearchInputChange = (e) => {
    e.stopPropagation();
    setSearchFor(e.target.value);
    if (typeof onSearchInput === 'function') onSearchInput(e.target.value);
    if (isAsyncDropdown) {
      if (!Boolean(e.target.value)) {
        setOptionsCallback([]);
        setAsyncLoading(false);
      } else {
        setAsyncLoading(true);
      }
    }
  };

  useClickOutSide([ref], () => {
    handleDropdownClose();
  });

  useEffect(() => {
    if (inputRef.current) {
      if (shouldShowInput) inputRef.current.focus();
      else inputRef.current.blur();
    }
  }, [shouldShowInput]);

  useEffect(() => {
    if (Boolean(defaultValue) && !isLoading) {
      onChange(defaultValue!);
    }
  }, [isLoading]);

  return (
    <div
      style={{
        position: 'relative',
      }}
    >
      <FieldLabel necessityIndicator={necessityIndicator} label={label} />
      <div
        ref={ref}
        style={{
          width: '100%',
        }}
      >
        <InputContainer
          tabIndex={tabIndex}
          theme={theme}
          validation={isValidated}
          role="select"
          aria-label={ariaLabelBy}
          onClick={() => {
            if (!isDisabled) {
              setShowDropDown(true);
            }
          }}
          shouldShowDropDown={shouldShowDropDown}
          isDisabled={isDisabled}
        >
          <InfoPanel theme={theme} shouldShowDropDown={shouldShowDropDown} validation={isValidated}>
            {value && Array.isArray(value) && Boolean(value.length) && shouldAllowMultiple ? (
              <SelectedOptionsContainer onChange={onChange} refKey={refKey} value={value} />
            ) : null}
            <SelectContainer itemHeight={itemHeight}>
              {shouldShowInput ? (
                <MultiSelectQueryInput
                  ref={inputRef}
                  value={searchFor}
                  shouldShowDropDown={shouldShowDropDown}
                  disabled={isLoading || isDisabled}
                  role="input"
                  aria-label="Search An Item Here"
                  placeholder={
                    isLoading ? 'Loading... Please wait...' : placeHolder ?? 'Search here...'
                  }
                  onChange={onSearchInputChange}
                />
              ) : (
                <DropdownSingleSelectedInput
                  labelKey={labelKey}
                  placeHolder={placeHolder}
                  shouldShowDropDown={shouldShowDropDown}
                  value={value}
                  shouldAllowMultiple={shouldAllowMultiple}
                />
              )}
              <DropdownIconWrapper>
                {isAsyncLoading && isAsyncDropdown ? (
                  <Spinner size="medium" accessibilityLabel="Loading results, please wait..." />
                ) : shouldShowDropDown ? (
                  <ChevronUpIcon color="feedback.icon.neutral.lowContrast" size="medium" />
                ) : (
                  <ChevronDownIcon color="feedback.icon.neutral.lowContrast" size="medium" />
                )}
              </DropdownIconWrapper>
            </SelectContainer>
          </InfoPanel>
        </InputContainer>
        <FieldFooter errorText={errorText} validation={isValidated} helpText={helpText} />

        {shouldShowDropDown && (
          <OptionList
            availableOptions={availableOptions}
            isValidated={isValidated}
            itemHeight={itemHeight}
            shouldAllowMultiple={shouldAllowMultiple}
            shouldCloseDropdownOnSelect={shouldCloseDropdownOnSelect}
            handleDropdownClose={handleDropdownClose}
            onChange={onChange}
            onSearchInput={onSearchInput}
            setSearchFor={setSearchFor}
            refKey={refKey}
            renderCustomOption={renderCustomOption}
            value={value}
            isVirtualized={isVirtualized}
            visibleOptionsCount={visibleOptionsCount}
            emptyOption={() => (
              <EmptyOption
                availableOptions={availableOptions}
                itemHeight={itemHeight}
                searchFor={searchFor}
                isSearchable={isSearchable}
                shouldShowDropDown={shouldShowDropDown}
              />
            )}
          />
        )}
      </div>
    </div>
  );
};
