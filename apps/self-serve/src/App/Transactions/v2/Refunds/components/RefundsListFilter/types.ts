// TODO: Fix imports, currently its out of scope from phase 1;
// @ts-nocheck
import { Option, Options } from '@dashboard/shared-ui/components/Dropdown/types';
import type { RouteComponentProps } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';
import { User } from '@dashboard/shared-utils/typings';
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
