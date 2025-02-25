import type { RazorpayUser } from '../../../common';
import type { PaymentsDashboardUserGetters } from './PaymentsDashboardUserGetters';
import type { PaymentsDashboardUserMethods } from './PaymentsDashboardUserMethods';

export type PaymentsDashboardUser = Partial<RazorpayUser> &
  PaymentsDashboardUserGetters &
  PaymentsDashboardUserMethods;
