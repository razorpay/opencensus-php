import { DashboardGraphQLMaybe, DashboardGraphQLVendorPaymentInvoiceAttachment, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLVendorPaymentInvoice = {
  __typename?: 'DashboardGraphQLVendorPaymentInvoice';
  invoiceAttachment?: DashboardGraphQLMaybe<DashboardGraphQLVendorPaymentInvoiceAttachment>;
  invoiceNumber?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
};