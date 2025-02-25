import { DashboardGraphQLQrCodeDate, DashboardGraphQLMaybe, DashboardGraphQLScalars, DashboardGraphQLQrCodePaymentDetail, DashboardGraphQLQrCodeStatusEnum, DashboardGraphQLQrCodeTypeEnum, DashboardGraphQLQrCodeUsageEnum } from './index';
export type DashboardGraphQLQrCode = {
  __typename?: 'QRCode';
  /** QR code date details */
  dates: DashboardGraphQLQrCodeDate;
  /** A brief description about the QR Code */
  description?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  /** Unique identifier of the QR Code */
  id: DashboardGraphQLScalars['ID'];
  /** The URL of the QR Code */
  imageURL: DashboardGraphQLScalars['String'];
  /** Indicates if the QR Code should accept payments of specific amounts or any amount */
  isFixedAmount?: DashboardGraphQLMaybe<DashboardGraphQLScalars['Boolean']>;
  /** Label entered to identify the QR Code */
  name?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  /** QR Code payment details */
  paymentDetails: DashboardGraphQLQrCodePaymentDetail;
  /** Indicates the status of the QR Code */
  status: DashboardGraphQLQrCodeStatusEnum;
  /** The type of the QR Code */
  type: DashboardGraphQLQrCodeTypeEnum;
  /** Indicates if the QR Code should be allowed to accept single payment or multiple payments */
  usage: DashboardGraphQLQrCodeUsageEnum;
};