import React, { useEffect, useRef, useState, useContext } from 'react';
import {
  Text,
  ChevronDownIcon,
  Spinner,
  AsyncDropdownContext,
} from 'merchant_common/views/Reports/components';
import { useTheme, useClickOutSide } from 'merchant_common/views/Reports/hooks';
import {
  DropdownIconWrapper,
  InfoPanel,
  InputContainer,
  MultiSelectQueryInput,
  SelectContainer,
} from './styled';
import { DropdownPropsType } from './types';
import { useDropdownOptions } from './hooks/useDropdownOptions';
import { DropdownSingleSelectedInput } from './Components/SelectedInfo';
import { EmptyOption } from './Components/EmptyInfoOption';
import { OptionList } from './Components/OptionList';
import { SelectedOptionsContainer } from './Components/SelectedOptionsContainer';

export const Dropdown = <ItemType, AllowMultiple, Virtualized>(
  props: DropdownPropsType<ItemType, AllowMultiple, Virtualized>,
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
    // as in case of components full potential
  } = props as unknown as DropdownPropsType<Record<string, unknown>, true, true>;

  const { theme } = useTheme();
  const [searchFor, setSearchFor] = useState('');
  const [shouldShowDropDown, setShowDropDown] = useState(false);
  const isValidated = validate();
  const refKey = typeof labelKey === 'string' ? labelKey : undefined;

  const { isAsyncDropdown, setOptionsCallback, isAsyncLoading, setAsyncLoading } =
    useContext(AsyncDropdownContext);

  const ref = useRef<HTMLInputElement>(null);
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
    <div>
      {Boolean(label) && (
        <Text variant="body" type="normal" weight="bold">
          {label}
        </Text>
      )}
      <InputContainer
        ref={ref}
        label={label}
        validation={isValidated}
        role="select"
        aria-label={ariaLabelBy}
        onClick={() => {
          setShowDropDown(true);
        }}
      >
        <InfoPanel theme={theme} shouldShowDropDown={shouldShowDropDown} validation={isValidated}>
          {value && Array.isArray(value) && value.length && shouldAllowMultiple ? (
            <SelectedOptionsContainer onChange={onChange} refKey={refKey} value={value} />
          ) : null}
          <SelectContainer itemHeight={itemHeight}>
            {shouldShowInput ? (
              <MultiSelectQueryInput
                ref={inputRef}
                value={searchFor}
                shouldShowDropDown={shouldShowDropDown}
                disabled={isLoading}
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
              />
            )}
            <DropdownIconWrapper>
              {isAsyncLoading && isAsyncDropdown ? (
                <Spinner size="medium" accessibilityLabel="Loading results, please wait..." />
              ) : (
                <ChevronDownIcon color="feedback.icon.neutral.lowContrast" size="medium" />
              )}
            </DropdownIconWrapper>
          </SelectContainer>
        </InfoPanel>

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
      </InputContainer>
      {Boolean(helpText) && (
        <Text
          variant="caption"
          type="subdued"
          weight="regular"
          color={
            isValidated ? 'surface.text.subdued.lowContrast' : 'feedback.text.negative.lowContrast'
          }
        >
          {isValidated ? helpText : `Mandatory Field: ${helpText}`}
        </Text>
      )}
    </div>
  );
};
