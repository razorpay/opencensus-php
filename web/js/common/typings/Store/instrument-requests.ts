import { PaymentMethodsFields } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings/section';
import User from 'common/typings/User';

export type CommonInstrumentRequestInfo = {
  created_at: number;
  description: string;
  fade_comment: string;
  merchant_instrument_request_id: string;
  name: string;
  path: string;
  slug: string;
  status: string;
  icon?: string;
};

export type LeafListItem = {
  header: string;
  list: CommonInstrumentRequestInfo[];
  docLink?: string;
  listDescription?: string;
  listHeader?: string;
  slug?: string;
};

export type InstrumentListItem = CommonInstrumentRequestInfo & {
  actionItems: Record<string, string>;
  capture_info_before_mir: undefined;
  collect_info: undefined;
  intermediateList?: InstrumentListItem[];
  leafList: LeafListItem[];
  slug: PaymentMethodsFields;
  additionalCondition: (user: User) => boolean;
};

export type InstrumentsList = InstrumentListItem[];

export type InstrumentRequestsReducerState = {
  pg: InstrumentsList;
  leafInstrument: InstrumentListItem | null;
  intermediateInstrument: InstrumentListItem | null;
  loading: boolean;
};
