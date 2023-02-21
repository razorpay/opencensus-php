import React from 'react';
import {
  DropDownItem,
  DropDownItems,
  DropDownMenu,
  DropdownValue,
  InputContainer,
  InputField,
  LeftIconContainer,
  SearchResult,
  ValueContainer,
} from './styled';
import { COUNTRY_CODES } from './constant';

export interface CountryCodeInputPropsInterface {
  dialCode?: string;
  value?: string;
  onDialCodeChange?: (value: string) => void;
  onContactChange?: (value: string) => void;
  onChange?: (value: { dialCode: string; value: string }) => void;
}

let defaultCountryData: {
  value: string;
  label: string;
  country: string;
};
const countryListData = COUNTRY_CODES.map((country) => {
  const countryData = {
    value: `${country.dial_code}`,
    label: `<strong>${country.dial_code}</strong> ${country.name}`,
    country: country.code.toLowerCase(),
  };
  if (country.code === 'IN') {
    defaultCountryData = countryData;
  }
  return countryData;
});

const CountryCodeInput = (props: CountryCodeInputPropsInterface): JSX.Element => {
  const dropdownMenuRef = React.useRef<HTMLDivElement>();

  const [countryData, setCountryData] = React.useState<typeof countryListData[number]>(() => {
    return (
      countryListData.find((countryData) => countryData.value === props.dialCode) ||
      defaultCountryData
    );
  });
  const [phoneNumber, setPhoneNumber] = React.useState(props.value);

  const [filter, setFilter] = React.useState('');
  const [isDropdownVisible, setDropdownVisible] = React.useState(false);
  const [searchResult, setResult] = React.useState(countryListData);

  const focusInput = () => {
    setDropdownVisible(true);
  };

  const handleEscapePress = React.useCallback((event) => {
    if (event.which === 27) {
      setDropdownVisible(false);
    }
  }, []);

  const handleDocumentClick = React.useCallback((event) => {
    const target = event.target;
    let isFound = false;
    if (target && dropdownMenuRef.current?.contains(target)) {
      isFound = true;
    }
    if (!isFound) {
      setDropdownVisible(false);
    }
  }, []);

  React.useEffect(() => {
    if (!filter) {
      setResult(countryListData);
      return;
    }
    setResult(
      countryListData.filter((country) =>
        country.label.toLowerCase().includes(filter.toLowerCase()),
      ),
    );
  }, [filter]);

  React.useEffect(() => {
    document.addEventListener('keydown', handleEscapePress);
    document.addEventListener('click', handleDocumentClick);
    return () => {
      document.removeEventListener('keydown', handleEscapePress);
      document.removeEventListener('click', handleDocumentClick);
    };
  }, [handleEscapePress, handleDocumentClick]);

  return (
    <DropDownMenu ref={dropdownMenuRef} className="country-code-input">
      <ValueContainer>
        <DropdownValue onClick={focusInput} data-testid="dialCodeSelector">
          <span className={`flag ${countryData.country}`} />
          <span data-testid="dialCodeValue" className="dial-code">
            {countryData.value || ''}
          </span>
          <i className="i i-chevron-down" />
        </DropdownValue>
        <input
          data-testid="contactInput"
          value={phoneNumber}
          onChange={(e) => {
            const inputValue = e.target.value;
            setPhoneNumber(inputValue);
            props.onContactChange?.(inputValue);
            props.onChange?.({
              dialCode: countryData.value,
              value: inputValue,
            });
          }}
          type="tel"
        />
      </ValueContainer>
      {isDropdownVisible && (
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
              onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                setFilter(e.target.value);
              }}
            />
            <i className="i i-search" />
          </InputContainer>
          <SearchResult>
            {searchResult.map((countryData) => (
              <DropDownItem
                key={countryData.country}
                onClick={(e) => {
                  e.stopPropagation();
                  props.onChange?.({
                    dialCode: countryData.value,
                    value: phoneNumber || '',
                  });
                  props.onDialCodeChange?.(countryData.value);
                  setCountryData(countryData);
                  setFilter('');
                  setDropdownVisible(false);
                }}
              >
                <LeftIconContainer>
                  <span className={`flag ${countryData.country}`} />
                </LeftIconContainer>
                <span dangerouslySetInnerHTML={{ __html: countryData.label }} />
              </DropDownItem>
            ))}
            {searchResult.length === 0 && <div className="no-result">No result found</div>}
          </SearchResult>
        </DropDownItems>
      )}
    </DropDownMenu>
  );
};

export default CountryCodeInput;

CountryCodeInput.defaultProps = {
  dialCode: '+91',
  value: '',
};
