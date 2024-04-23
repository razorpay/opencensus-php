import { Option, Options } from '@dashboard/shared-ui/components/Dropdown/types';
import { RouteComponentProps } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';
import { SearchQueryParam } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { Duration, DurationOption } from 'apps/self-serve/src/App/Transactions/v2/common/types';

const {
  ID,
  EMAIL,
  METHOD,
  CONTACT,
  COUNTRY_CODE,
  ORDER_ID,
  PAYMENT_ID,
  STATUS,
  FROM,
  TO,
  PUBLIC_STATUS,
  NOTES,
} = SearchQueryParam;

export type SearchQueryParamType =
  | typeof ID
  | typeof STATUS
  | typeof METHOD
  | typeof CONTACT
  | typeof EMAIL
  | typeof COUNTRY_CODE
  | typeof ORDER_ID
  | typeof PAYMENT_ID
  | typeof FROM
  | typeof TO
  | typeof STATUS
  | typeof METHOD
  | typeof PUBLIC_STATUS
  | typeof NOTES;

interface SearchArgs {
  [STATUS]: string;
  [COUNTRY_CODE]?: string;
  [FROM]: number;
  [TO]: number;
}

export interface PaymentsListFilterProps extends RouteComponentProps {
  onSubmit: (args: SearchArgs) => void;
  loading: boolean;
}

export interface ExtraFiltersModalProps {
  handleSearch: (params) => void;
}

export interface DefaultStatusAndOptions {
  defaultStatusValue: string;
  defaultStatusOption: Option;
}

export interface DefaultMethodAndOption {
  defaultMethodValue: string;
  defaultMethodOption: Option;
}

export interface DefaultChannelAndOption {
  defaultChannelValue: string;
  defaultChannelOption: Option;
}

export interface DefaultValuesAndOptions {
  defaultDate: Duration;
  defaultPaymentDuration: DurationOption;
  defaultMethodValue: string;
  defaultStatusValue: string;
  defaultStatusOption: Option;
  defaultSearchByOption: Option;
  defaultSearchByValue: string;
  defaultMethodOption: Option;
  defaultCountryCodeValue: string;
  defaultChannelValue: string;
  defaultChannelOption: Option;
}

export interface AllOptions {
  paymentDurationOptions: Options;
  paymentMethodOptions: Options;
  statusOptions: Options;
  searchByOptions: Options;
  countryCodeOptions: Options;
  paymentChannelOptions: Options;
}

export type PaymentMethodOption = {
  title: string;
  value: string;
};
