import { durationOptionsMap } from './constants';

export enum Page {
  PAYMENTS = 'payments',
  FAILED_PAYMENTS = 'failed payments',
  REFUNDS = 'refunds',
  ORDERS = 'orders',
  DISPUTES = 'disputes',
}

export enum View {
  LOADING = 'loading',
  FTUX = 'ftux',
  FAILED_FTUX = 'failed-ftux',
  LIST = 'list',
}

export type Paginate = (args: Record<string, unknown>) => void;

export interface ListContainerProps<T> {
  count: number;
  skip: number;
  paginate: Paginate;
  loading: boolean;
  items: T[];
  shouldShowCustomTransactionTabView: boolean;
  selectedColumnsList: string[];
  isOmniView: boolean;
  isJnKOmniEnabled: boolean;
  isPosOrderIDEnabled: boolean;
}

export interface Collection<T> {
  entity: 'collection';
  count: number;
  has_more: boolean;
  items: T[];
}

export interface Duration {
  from: number;
  to: number;
}

export type DurationOptionsMap = typeof durationOptionsMap;

export type DurationOption = { title: string; value: keyof DurationOptionsMap };

export interface Track {
  objectName: string;
  actionName?: string;
  screen?: string;
  properties?: Record<string, unknown>;
}

export interface TrackSearchButton {
  searchBy: string;
  searchByValue: string;
  pathname: string;
  countryCode?: string;
}
