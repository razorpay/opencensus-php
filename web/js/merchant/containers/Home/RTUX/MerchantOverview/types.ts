import { OpenModalType, User } from 'common/typings';
import { Currency } from 'merchant/views/Transactions/v2/Payments/types';

export enum SettlementStatusBadge {
  created = 'created',
  initiated = 'initiated',
  on_track = 'on_track',
  processed = 'processed',
  delayed = 'delayed',
  failed = 'failed',
  blocked = 'blocked',
  paused = 'paused',
  skipped = 'skipped',
}

export enum TimelineItemIconKeys {
  in_progress = 'in_progress',
  done = 'done',
  loading = 'loading',
}

export const enum TodaySettlementKeys {
  SETL_TODAY_MULTIPLE = 'SETL_TODAY_MULTIPLE',
  SETL_TODAY_PROCESSED_BEFORE_THAN_8PM = 'SETL_TODAY_PROCESSED_BEFORE_THAN_8PM',
  SETL_TODAY_PROCESSED_AFTER_THAN_8PM = 'SETL_TODAY_PROCESSED_AFTER_THAN_8PM',
  SETL_TODAY_DELAYED = 'SETL_TODAY_DELAYED',
  SETL_TODAY_CREATED = 'SETL_TODAY_CREATED',
  SETL_TODAY_FAIL_SOH = 'SETL_TODAY_FAIL_SOH',
  SETL_TODAY_FAIL_RETRY_SLA_NOT_BREACHED = 'SETL_TODAY_FAIL_RETRY_SLA_NOT_BREACHED',
  SETL_TODAY_FAIL_SLA_BREACHED = 'SETL_TODAY_FAIL_SLA_BREACHED',
}

export const enum UpcomingSettlementKeys {
  UPCOMING_SETL_BLOCK_SOH = 'UPCOMING_SETL_BLOCK_SOH',
  UPCOMING_SETL_BLOCK_FOH = 'UPCOMING_SETL_BLOCK_FOH',
  UPCOMING_SETL_BLOCK_MOH = 'UPCOMING_SETL_BLOCK_MOH',
  UPCOMING_SETL_SKIPPED_NO_NEXT_SETTLEMENT = 'UPCOMING_SETL_SKIPPED_NO_NEXT_SETTLEMENT',
  UPCOMING_SETL_SKIPPED_AMOUNT_LESS_THAN_ONE = 'UPCOMING_SETL_SKIPPED_AMOUNT_LESS_THAN_ONE',
  UPCOMING_SETL_SKIPPED_AMOUNT_GREATER_THAN_BALANCE = 'UPCOMING_SETL_SKIPPED_AMOUNT_GREATER_THAN_BALANCE',
  UPCOMING_SETL_ON_TRACK = 'UPCOMING_SETL_ON_TRACK',
}

export type IMultipleSettementFraction = {
  status: SettlementStatusBadge;
  count: number;
  amount: number;
};

export interface ITodaysSettlement {
  data: ITodaySettlementData;
  settlement_currency: Currency;
  isTodaySettlementPastProcessedSLA: boolean;
  isTodayMultipleSettlements: boolean;
}

export interface IPreviousSettlement {
  data: IPreviousSettlementData;
  settlement_currency: Currency;
  showOnlyPreviousSettlement: boolean;
}

export interface IUpcommingSettlement {
  data: IUpcomingSettlementData;
  settlement_currency: Currency;
}

export interface ITodaySettlementData {
  total_amount: number;
  total_count: number;
  delayed_total_amount: number;
  delayed_count: number;
  failed_total_amount: number;
  failed_count: number;
  created_total_amount: number;
  created_count: number;
  processed_total_amount: number;
  processed_count: number;
  title_key: TodaySettlementKeys;
}

export interface IUpcomingSettlementData {
  settlement_amount: number;
  next_settlement_time: number;
  title_key: UpcomingSettlementKeys;
}

export interface IPreviousSettlementData {
  total_amount: number;
  total_count: number;
  created_at: number;
}

export interface ISettlementData {
  current_balance: number;
  current_balance_currency: Currency;
  settlement_currency: Currency;
  today?: ITodaySettlementData;
  upcoming_settlement?: IUpcomingSettlementData;
  previous?: IPreviousSettlementData;
}

export interface IMerchantOverview {
  data?: {
    hero_card_data: IMerchantOverviewData;
  };
  type: string;
  user: User;
}

export interface IMerchantOverviewData {
  is_transacted: boolean;
  is_settlement: boolean;
  settlement_schedule?: string;
  settlement?: ISettlementData;
}

export interface INonSettlement {
  fetchHolidayList: () => Promise<Record<string, string>>;
  fetchSchedule: () => Promise<Record<string, string>>;
  fetchSettlementConfig: () => Promise<Record<string, string>>;
  openModal: OpenModalType;
  is_transacted: Pick<IMerchantOverviewData, 'is_transacted'>;
  settlement_schedule: Pick<IMerchantOverviewData, 'settlement_schedule'>;
}

export interface ISettlement {
  settlement: ISettlementData;
}

export interface ITimelineItemWithAmount {
  amount: number;
  currency: Currency;
  status: SettlementStatusBadge;
  heading: string;
  subheading: string;
  action?: JSX.Element | null;
}

export interface ISettlementConfig {
  shouldShowToday: boolean;
  isTodaySettlementPastProcessedSLA: boolean;
  isTodayMultipleSettlements: boolean;
  shouldShowPrevious: boolean;
  shouldShowUpcomming: boolean;
  shouldShowUpcommingBlock: boolean;
  shouldShowOnlyPreviousSettlement: boolean;
}

export interface IGetUpcommingSettlementOptions {
  title_key: string;
  next_settlement_time: number;
  settlement_currency: Currency;
}

export interface IGetUpcommingSettlementData {
  status: SettlementStatusBadge;
  subheading: string;
  action: JSX.Element | null;
}

export interface IGetTodaySingleSettlementContent {
  status: SettlementStatusBadge;
  subheading: string;
  action: JSX.Element | null;
}

export interface IGetNonSettlementCardContentOptions {
  is_transacted: Pick<IMerchantOverviewData, 'is_transacted'>;
  settlement_schedule: Pick<IMerchantOverviewData, 'settlement_schedule'>;
  openModal: (modalOptions) => void;
}

export interface IGetNonSettlementCardContent {
  title: string;
  subtitle: string;
  action: JSX.Element;
  illustration: string;
}

export interface IAnalyticsProperties {
  analyticsProperties: Record<string, any>;
}
