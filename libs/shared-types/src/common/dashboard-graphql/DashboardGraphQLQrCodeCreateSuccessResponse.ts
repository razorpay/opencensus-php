import { DashboardGraphQLMutationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLMaybe, DashboardGraphQLQrCode } from './index';
export type DashboardGraphQLQrCodeCreateSuccessResponse = DashboardGraphQLMutationResponseInterface & {
  __typename?: 'QRCodeCreateSuccessResponse';
  code: DashboardGraphQLScalars['PositiveInt'];
  message?: DashboardGraphQLMaybe<DashboardGraphQLScalars['String']>;
  qrCode: DashboardGraphQLQrCode;
  success: DashboardGraphQLScalars['Boolean'];
};