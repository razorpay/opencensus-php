import { I18nifyCountryCodeType } from 'merchant/views/StoreSettings/StoreCreateOrEdit/types';

export type CountryOption = { label: string; value: I18nifyCountryCodeType };
export type StateOption = { label: string; value: string };
export type CityOption = { label: string; value: string };

export type GeoLocationOptions = {
  countries: CountryOption[];
  states: StateOption[];
  cities: CityOption[];
};

export type GeoLocation = {
  country: string;
  state: string;
  city: string;
};

export type LocationAndStoreInfoContainerProps = {
  isLoading: boolean;
};
