import { RouteComponentProps } from 'common/deprecated/RouteComponentProps';

import { Option, Options } from 'common/components/Dropdown/types';

import { refundsDurationOptionsMap } from './constants';

export type DurationOption = { title: string; value: keyof typeof refundsDurationOptionsMap };

export interface Duration {
  from: number | null;
  to: number | null;
}

interface SearchParams extends Duration {
  public_status: string;
}

export interface RefundsListFilterProps extends RouteComponentProps {
  onSubmit: (args: SearchParams) => void;
  loading: boolean;
}

export interface DefaultDateAndOption {
  defaultDate: Duration;
  defaultDuration: DurationOption;
}

export interface DefaultStatusAndOption {
  defaultStatusValue: string;
  defaultStatusOption: Option;
}

export interface DefaultValuesAndOptions {
  defaultDate: Duration;
  defaultRefundsDuration: DurationOption;
  defaultStatusValue: string;
  defaultStatusOption: Option;
  defaultSearchByOption: Option;
  defaultSearchByValue: string;
}

export interface AllOptions {
  refundsDurationOptions: Options;
  statusOptions: Options;
  searchByOptions: Options;
}
