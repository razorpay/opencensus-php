import type { DASHBOARD_MODE } from '../../../../common';
import type { PaymentsDashboardUser } from '../../user';

export type PaymentsDashboardSessionReducerState = {
  user: PaymentsDashboardUser;
  org: Record<string, unknown>;
  mode: DASHBOARD_MODE;
  partnerMode: DASHBOARD_MODE;
  modeFormatted: 'Test' | 'Live';
  partnerModeFormatted: 'Test' | 'Live';
  highlightMode: boolean;
  isTourVisible: boolean;
  isUsingPartnerMode: boolean;
  user_segment_data: null;
  isTagsLoaded: boolean;
};
