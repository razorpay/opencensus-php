import { Option, Options } from 'common/components/Dropdown/types';
import { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { SearchQueryParam } from 'merchant/views/Transactions/v2/common/constants';
import { Duration, DurationOption } from 'merchant/views/Transactions/v2/common/types';

const { STATUS, FROM, TO, PAYMENT_ID, ID } = SearchQueryParam;

export interface SearchArgs {
  [STATUS]: string;
  [FROM]: number;
  [TO]: number;
  [PAYMENT_ID]?: string;
  [ID]?: string;
}
export interface DisputeListFilterProps extends RouteComponentProps {
  onSubmit: (args: SearchArgs) => void;
  loading: boolean;
}

export interface DefaultStatusAndOptions {
  defaultStatusValue: string;
  defaultStatusOption: Option;
}

export interface DefaultValuesAndOptions {
  defaultDate: Duration;
  defaultDisputeDuration: DurationOption;
  defaultStatusValue: string;
  defaultStatusOption: Option;
  defaultSearchByValue: string;
}

export interface AllOptions {
  disputeDurationOptions: Options;
  statusOptions: Options;
}

export type SelectedFilterType = Record<string, string[]>;
