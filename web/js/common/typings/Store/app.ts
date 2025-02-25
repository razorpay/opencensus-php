import { PaymentsDashboardAppReducerState } from '@libs/shared-types/payments';
import { LADashboardAppReducerState } from '@libs/shared-types/la';
import { StrictMerge } from '@libs/shared-types';

export type AppReducerState = StrictMerge<
  PaymentsDashboardAppReducerState,
  LADashboardAppReducerState
>;
