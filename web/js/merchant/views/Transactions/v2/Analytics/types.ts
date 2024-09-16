import { Dispatch, SetStateAction } from 'react';
import { Environments, OpenModalType, Store } from 'common/typings';
import { Duration } from 'merchant/views/Transactions/v2/common/types';
import { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { Currency } from 'merchant/views/Transactions/v2/Payments/types';
import { Option } from 'common/components/Dropdown/types';

export enum TxnStatus {
  CAPTURED = 'captured',
  REFUNDED = 'refunded',
  PROCESSED = 'processed',
  FAILED = 'failed',
}

export enum PaymentTypes {
  Failed = 'Failed',
  Refunds = 'Refunds',
  Disputes = 'Disputes',
}

export enum EnvironmentsModes {
  LIVE = 'live',
  TEST = 'test',
}

type PhaseInfoType = {
  count: number;
  amount: number;
};

export interface DisputeDataResponse {
  totalDisputeAmount: number;
  totalDisputesCount: number;
  openDisputes: PhaseInfoType;
  underReviewDisputes: PhaseInfoType;
  wonDisputes: PhaseInfoType;
  lostDisputes: PhaseInfoType;
}

export type DisputeTypeState = {
  count: number;
  amount: number;
  loading: boolean;
  failed: boolean;
};

export type PaymentResponse = {
  paymentCapturedCount: number;
  paymentCapturedAmount: number;
  refundCount: number;
  refundAmount: number;
  paymentByMethod: Array<SplitPaymentMethod>;
};

export type PaymentDataHookParams = {
  isRefundPendingEnabled: boolean;
};

export type PaymentDataHookResponse = {
  paymentsData: PaymentResponse;
  fetchPaymentData: (duration: Duration) => void;
  loading: boolean;
  failed: boolean;
};

export type RefundResponse = {
  refunded: {
    count: number;
    amount: number;
  };
  processing: {
    count: number;
    amount: number;
  };
  failed: {
    count: number;
    amount: number;
  };
};

export type RefundDataHookParams = {
  isRefundPendingEnabled: boolean;
};

export type RefundDataHookResponse = {
  refundsData: RefundResponse;
  fetchRefundData: (duration: Duration) => void;
  loading: boolean;
  failed: boolean;
};

export type FailedDataHookResponse = {
  failedPaymentsData: number;
  failureInfo: FailedOverviewResult;
  fetchFailedPaymentsData: (dateDuration: Duration) => void;
  loading: boolean;
  failed: boolean;
};

export type SuccessRateDataHookResponse = {
  successRateData: number;
  fetchSuccessRateData: (dateDuration: Duration) => void;
  loading: boolean;
  failed: boolean;
};

export type DisputeDataHookResponse = {
  disputeData: DisputeDataResponse;
  fetchDisputesData: (dateDuration: Duration) => void;
  loading: boolean;
  failed: boolean;
};

export type SplitPaymentMethod = {
  label: string;
  value: number;
};

export interface LandingAnalyticsProps {
  mode: Environments;
  user: Store['session']['user'];
  fetchHolidayList: () => Promise<Record<string, string>>;
  fetchSchedule: () => Promise<Record<string, string>>;
  fetchSettlementConfig: () => Promise<Record<string, string>>;
}

export interface TopOverviewContainerProps {
  isPaymentsDataLoading: boolean;
  isPaymentsDataFailed: boolean;
  paymentCapturedAmount: number;
  paymentCapturedCount: number;
  paymentByMethod: Array<SplitPaymentMethod>;
  isMobile: boolean;
  currency: Currency;
  shouldShowSrBanner: boolean;
  successRateData: number;
  durationOption: Option;
}

export interface CapturedPaymentCardProps {
  openModal: OpenModalType;
  paymentCapturedAmount: number;
  paymentCapturedCount: number;
  isMobile: boolean;
  currency: Currency;
  durationOption: Option;
}

export interface PaymentMethodSplitProps {
  paymentByMethod: Array<SplitPaymentMethod>;
  isMobile: boolean;
  shouldShowSrBanner: boolean;
  successRateData: number;
  durationOption: Option;
}

export enum SuccessRateBannerSection {
  PAYMENTS_OVERVIEW = 'Payments Overview',
  FAILED_PAYMENTS_OVERVIEW = 'Failed Payments Overview',
}

export interface SuccessRateBannerProps extends RouteComponentProps {
  successRateData: number;
  section: SuccessRateBannerSection;
}

export enum EntityOverviewType {
  Failed = 'Failed',
  Refunds = 'Refunds',
}

export interface EntityAnalyticsProps {
  type: EntityOverviewType;
}

interface BottomOverviewData {
  refund: {
    amount: number;
    count: number;
    loading: boolean;
    failed: boolean;
  };
  disputes: {
    amount: number;
    open: number;
    underReview: number;
    loading: boolean;
    failed: boolean;
  };
  failed: {
    amount: number;
    loading: boolean;
    failed: boolean;
  };
}
export interface BottomOverviewProps {
  mode: Environments;
  currency: Currency;
  data: BottomOverviewData;
  durationOption: Option;
}

export interface BottomOverviewCardsData {
  mode: Environments;
  data: BottomOverviewData;
}

export interface BottomOverviewCardFooterProps {
  refundCount: number;
  openDisputesCount: number;
  underReviewDisputesCount: number;
}

export interface BottomOverviewCardData {
  name: PaymentTypes;
  loading: boolean;
  value: number;
  visible: boolean;
  isAmount: boolean;
  failed: boolean;
}

export interface BottomOverviewCardProps extends RouteComponentProps {
  currency: Currency;
  data: BottomOverviewCardData;
  footerValues: BottomOverviewCardFooterProps;
  durationOption: Option;
}

export const enum RefetchDataTypes {
  Payments = 'Payments',
  All = 'All',
}

export type RefetchData = PaymentTypes | RefetchDataTypes;

interface PaymentAnalyticsDataMethod {
  total: number;
  last_updated_at: number;
  result: Array<{
    method: string;
    value: number;
  }>;
}

export interface AnalyticsAPISegemntResult {
  status: string;
  value: number;
}

export interface AccumalateResponse {
  countData: Array<AnalyticsAPISegemntResult>;
  sumData: Array<AnalyticsAPISegemntResult>;
  status: Array<string>;
  exclude?: boolean;
}

export interface AccumalateData {
  data: Array<AnalyticsAPISegemntResult>;
  status: Array<string>;
  exclude?: boolean;
}

interface PaymentAnalyticsDataSegment {
  total: number;
  last_updated_at: number;
  result: Array<AnalyticsAPISegemntResult>;
}
export interface PaymentAnalyticsAPIResponse {
  paymentcount: PaymentAnalyticsDataSegment;
  paymentsum: PaymentAnalyticsDataSegment;
  refundcountnormal: PaymentAnalyticsDataSegment;
  refundsumnormal: PaymentAnalyticsDataSegment;
  refundcountinstant: PaymentAnalyticsDataSegment;
  refundsuminstant: PaymentAnalyticsDataSegment;
  paymentbymethod: PaymentAnalyticsDataMethod;
}

export interface RefundsAnalyticsAPIResponse {
  refundcountnormal: PaymentAnalyticsDataSegment;
  refundsumnormal: PaymentAnalyticsDataSegment;
  refundcountinstant: PaymentAnalyticsDataSegment;
  refundsuminstant: PaymentAnalyticsDataSegment;
}

export interface DisputeAPIResponse {
  count: number;
  disputed_amount_sum: number;
}

export interface FailedPaymentsAPIResponse {
  [key: string]: {
    reason: string;
    count: number;
  }[];
}

export interface SuccessRateAPIResponse {
  code: string;
  name: string;
  sr: number;
  successful: number;
  total: number;
  intervals: Array<{
    from: number;
    to: number;
    sr: number;
    successful: number;
    total: number;
  }>;
}

export interface FailedOverviewResult {
  [key: string]: {
    value: number;
    failure_types: string[];
  };
}

export interface CardInfoProps {
  title: string;
  value: number;
  isLeader?: boolean;
  isMobile?: boolean;
  isAmount?: boolean;
  subtitle: string;
  toolTipText: string;
  isLoading: boolean;
  currency: Currency;
}

export interface AnalyticsBoilerPlateTile {
  title: string;
  value: number;
  isAmount?: boolean;
  subtitle: string;
  toolTipText: string;
}

export interface AnalyticsBoilerPlateData {
  lead: AnalyticsBoilerPlateTile;
  trail: AnalyticsBoilerPlateTile[];
}
export interface AnalyticsBoilerPlateProps {
  isLoading: boolean;
  isMobile: boolean;
  user: Store['session']['user'];
  data: AnalyticsBoilerPlateData;
}

export interface RefundsOverviewProps {
  mode: Store['session']['mode'];
  user: Store['session']['user'];
}

export interface LoadFailedProps {
  title: string;
  subtitle: string;
  height: number;
}

export interface PaymentSplitMethodData {
  labels: string[];
  segmentData: number[];
  segmentDataTotal: number;
}

export interface FailedPaymentsRequestPayload {
  entity: string;
  from: number;
  to: number;
  mode: string;
  group_by: {
    limit: number;
  };
}

export interface SuccessRateRequestPayload {
  entity: string;
  from: number;
  to: number;
  interval: number;
  mode: string;
  features: {
    use_alias: boolean;
  };
}

export interface FetchDisputesData {
  params: { status?: string; from: number; to: number };
  setState: Dispatch<SetStateAction<DisputeTypeState>>;
}
