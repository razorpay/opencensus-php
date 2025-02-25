import { DashboardGraphQLMaybe, DashboardGraphQLScalars } from './index';
export type DashboardGraphQLQrCodeDate = {
  __typename?: 'QRCodeDate';
  /** Unix timestamp at which the QR Code is created */
  createdAt?: DashboardGraphQLMaybe<DashboardGraphQLScalars['DateTime']>;
};