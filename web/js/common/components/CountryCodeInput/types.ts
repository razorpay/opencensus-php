export interface CountryCodeInputPropsInterface {
  dialCode?: string;
  value?: string;
  onDialCodeChange?: (value: string) => void;
  onContactChange?: (value: string) => void;
  onChange?: (value: { dialCode: string; value: string }) => void;
  showContactInput?: boolean;
}

type countryData = {
  value: string;
  label: string;
  country: string;
};

export interface CountryCodeDropDownItemProps {
  countryData: countryData;
  onSelect: (data: countryData) => void;
}
