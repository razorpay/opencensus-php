import { RouteComponentProps } from 'common/deprecated/RouteComponentProps';

import { Option, Options } from 'common/components/Dropdown/types';

import { refundsDurationOptionsMap } from './constants';
import { User } from 'common/typings';

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
  user: User;
}

export interface DefaultDateAndOption {
  defaultDate: Duration;
  defaultDuration: DurationOption;
}

export interface DefaultStatusAndOption {
  defaultStatusValue: string;
  defaultStatusOption: Option;
}

export interface DefaultChannelAndOption {
  defaultChannelValue: string;
  defaultChannelOption: Option;
}

export interface DefaultValuesAndOptions {
  defaultDate: Duration;
  defaultRefundsDuration: DurationOption;
  defaultStatusValue: string;
  defaultStatusOption: Option;
  defaultSearchByOption: Option;
  defaultSearchByValue: string;
  defaultChannelValue: string;
  defaultChannelOption: Option;
}

export interface AllOptions {
  refundsDurationOptions: Options;
  statusOptions: Options;
  searchByOptions: Options;
  paymentChannelOptions: Options;
}

export interface ExtraFiltersModalProps {
  closeModal: () => void;
  handleSearch: (params) => void;
}
