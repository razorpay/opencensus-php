import { ShowNotificationType, User } from 'common/typings';
import React from 'react';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
interface MatchParams {
  id: string;
}

export enum VALUE_TYPE {
  AMOUNT = 'amount',
  CHIP = 'chip',
  DATE = 'date',
  TEXT = 'text',
}

export enum ERROR_TYPE {
  INVALID_ID = 'invalid_id',
  SERVER_ERROR = 'server_error',
}

type AmountTypes = 'info' | 'breakup' | 'subBreakup' | 'net';

export enum SettlementStatus {
  CREATED = 'created',
  PROCESSED = 'processed',
  FAILED = 'failed',
  INITIATED = 'initiated',
}

export enum SettlementFailedStatus {
  FOH_HOLD = 'FOH_HOLD',
  SOH_HOLD = 'SOH_HOLD',
  RETRYING = 'RETRYING',
  FAILED = 'FAILED',
}

export enum SettlementStatusIcons {
  IN_PROGRESS = 'IN_PROGRESS',
  DONE = 'DONE',
  FAILED = 'FAILED',
}

export interface SettlementDetailsInterface extends RouteComponentProps<MatchParams> {
  error: any;
  loading: boolean;
  fetchItem: (id: string) => Promise<void>;
  showNotification: ShowNotificationType;
  user: Required<User>;
}

export interface DateInfo {
  date: string;
  time: string;
}

type LocationState =
  | undefined
  | {
      prevPath?: string;
    };

export interface LayoutPropsInterface
  extends RouteComponentProps<
    Record<string, string | undefined>,
    Record<string, unknown>,
    LocationState
  > {
  children: React.ReactNode;
  settlementId: string;
}

export interface SettlementPropsInterface {
  amount: number;
  created_at: number;
  entity: string;
  fees: number;
  tax: number;
  id: string;
  status: SettlementStatus;
  utr: string;
}

export interface SettlementInfoInterface {
  id: string;
  name: string;
  type?: VALUE_TYPE;
  isCopy?: boolean;
  value: any;
  onItemCopy?: (additionalData) => void;
}

export interface ErrorScreenPropsInterface extends RouteComponentProps<MatchParams> {
  type: ERROR_TYPE;
  handleRefresh: () => void;
  isDetailsRevampFlow?: boolean;
}

export interface AmountPropsInterface {
  amount: number;
  currency?: string;
  type?: AmountTypes;
  color?: string;
  operator?: '+' | '-' | '';
}

interface Properties {
  Component: React.ElementType;
  props: {
    size?: string;
    weight?: string;
    type?: string;
  };
}

export type AmountTypeInterface = {
  [K in AmountTypes]: {
    rupeeConfig: Properties;
    paisaConfig: Properties;
  };
};

export interface TimelineJourneyInterface {
  id: SettlementStatus;
  status: string;
  timeline?: string;
}

export interface FaqInterface {
  isMobile: boolean;
  isInstantSettlement?: boolean;
}

export interface SettlementDetailViewInterface {
  fetchBreakupDetails: (payload: { id: string }) => Promise<void>;
  settlementId: string;
  breakupDetails: BreakupDetailsInterface;
  settlement: SettlementPropsInterface;
  user: Required<User>;
}

export interface BreakupItems {
  amount: number;
  component: string;
  count: number;
  fee: number;
  tax: number;
  type: 'credit' | 'debit';
}
export interface BreakupDetailsInterface {
  items: BreakupItems[];
  isBreakupNew: boolean;
  loading: boolean;
  error: null | string | string[];
}

export interface BreakupComponentInterface {
  id: string;
  name: string;
  amount: number;
  tooltipInfo?: string;
}

export interface BreakUpDetailsResponse {
  grossSettlements: {
    amount: number;
    entries: BreakupComponentInterface[];
    components: string[];
  };
  deductions: {
    amount: number;
    entries: BreakupComponentInterface[];
    components: string[];
  };
  netSettlements: {
    amount: number;
  };
}

export interface AlertInterface {
  shouldShouldFailedAlert: boolean;
  bannerConfig?: {
    heading: string;
    description: string;
    action?: any;
  };
}

export interface SettlementListFilters {
  id?: string;
  from?: string;
  to?: string;
  utr?: string;
  status?: string;
}
