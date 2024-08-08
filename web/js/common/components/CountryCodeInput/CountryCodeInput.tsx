// Todo: delete this file, it's available in @dashboard/shared-ui
import React, { ChangeEvent, useCallback, useEffect, useRef, useState } from 'react';

import { getCountryCodes } from 'common/utils/rzp-utils';

import CountryCodeDropDownItem from './CountryCodeDropDownItem';
import {
  CountryCodeContainer,
  DropDownItems,
  DropDownMenu,
  DropdownValue,
  InputContainer,
  InputField,
  SearchResult,
  ValueContainer,
} from './styled';
import { CountryCodeInputPropsInterface } from './types';

let defaultCountryData: {
  value: string;
  label: string;
  country: string;
};

const CountryCodeInput = ({
  dialCode,
  onChange,
  onContactChange,
  onDialCodeChange,
  value,
  showContactInput = true,
}: CountryCodeInputPropsInterface): JSX.Element => {
  const dropdownMenuRef = useRef<HTMLDivElement>();
  // prettier-ignore
  const [phoneNumber, setPhoneNumber] = useState(value);
  const [filter, setFilter] = useState('');
  const [isDropdownVisible, setDropdownVisible] = useState(false);
  const [countryListData, setCountryListData] = useState<
    { value: string; label: string; country: string }[]
  >([]);
  const [countryData, setCountryData] = useState<{
    value: string;
    label: string;
    country: string;
  } | null>(null);
  const [searchResult, setResult] = useState<typeof countryListData>([]);
  const chevronIcon = isDropdownVisible ? 'i-chevron-up' : ' i-chevron-down';

  useEffect(() => {
    const fetchCountryCodes = async () => {
      const countryCodes = await getCountryCodes();
      const formattedCountryListData = countryCodes.map((country) => ({
        value: `${country.dial_code}`,
        label: `<strong>${country.dial_code}</strong> ${country.name}`,
        country: country.code.toLowerCase(),
      }));
      setCountryListData(formattedCountryListData);
      setResult(formattedCountryListData);
      const initialCountryData =
        formattedCountryListData.find((country) => country.value === dialCode) ||
        formattedCountryListData.find((country) => country.country === 'in');
      setCountryData(initialCountryData);
    };
    fetchCountryCodes();
  }, [dialCode]);

  const focusInput = () => {
    setDropdownVisible(true);
  };

  const onPhoneInputChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      const inputValue = e.target.value;
      setPhoneNumber(inputValue);
      onContactChange?.(inputValue);
      onChange?.({
        dialCode: countryData?.value as string,
        value: inputValue,
      });
    },
    [onChange, onContactChange, setPhoneNumber, countryData?.value],
  );

  const onItemSelect = useCallback(
    (countryData: typeof defaultCountryData) => {
      onChange?.({
        dialCode: countryData.value,
        value: phoneNumber || '',
      });
      onDialCodeChange?.(countryData.value);
      setCountryData(countryData);
      setFilter('');
      setDropdownVisible(false);
    },
    [onChange, onDialCodeChange, phoneNumber],
  );

  const renderDropDownItem = useCallback(
    (countryData) => (
      <CountryCodeDropDownItem
        key={countryData.country}
        onSelect={onItemSelect}
        countryData={countryData}
      />
    ),
    [onItemSelect],
  );

  useEffect(() => {
    if (!filter) {
      setResult(countryListData);
      return;
    }
    setResult(
      countryListData.filter((country) =>
        country?.label.toLowerCase().includes(filter.toLowerCase()),
      ),
    );
  }, [filter]);

  useEffect(() => {
    function handleEscapePress(event: KeyboardEvent) {
      if (event.which === 27) {
        setDropdownVisible(false);
      }
    }
    function handleDocumentClick(event: MouseEvent) {
      const { target } = event;
      let isFound = false;
      if (target && dropdownMenuRef.current?.contains(target as Node)) {
        isFound = true;
      }
      if (!isFound) {
        setDropdownVisible(false);
      }
    }

    document.addEventListener('keydown', handleEscapePress);
    document.addEventListener('click', handleDocumentClick);
    return () => {
      document.removeEventListener('keydown', handleEscapePress);
      document.removeEventListener('click', handleDocumentClick);
    };
  }, []);

  return (
    <CountryCodeContainer>
      <DropDownMenu ref={dropdownMenuRef} className="country-code-input">
        <ValueContainer>
          <DropdownValue onClick={focusInput} data-testid="dialCodeSelector">
            <span className={`flag ${countryData?.country}`} />
            <span data-testid="dialCodeValue" className="dial-code">
              {countryData?.value || ''}
            </span>
            <i className={`i ${chevronIcon}`} />
          </DropdownValue>
          {showContactInput ? (
            <input
              data-testid="contactInput"
              value={phoneNumber}
              onChange={onPhoneInputChange}
              type="tel"
            />
          ) : null}
        </ValueContainer>
        {isDropdownVisible ? (
          <DropDownItems data-testid="dropdownItems">
            <InputContainer>
              <InputField
                autoCapitalize="none"
                autoComplete="off"
                autoCorrect="off"
                spellCheck="false"
                type="text"
                id="search"
                aria-autocomplete="list"
                placeholder="Search for country code"
                onChange={(e: ChangeEvent<HTMLInputElement>) => {
                  setFilter(e.target.value);
                }}
              />
              <i className="i i-search" />
            </InputContainer>
            <SearchResult>
              {searchResult.map(renderDropDownItem)}
              {searchResult.length === 0 && <div className="no-result">No result found</div>}
            </SearchResult>
          </DropDownItems>
        ) : null}
      </DropDownMenu>
    </CountryCodeContainer>
  );
};

export default CountryCodeInput;

CountryCodeInput.defaultProps = {
  dialCode: '+91',
  value: '',
};
