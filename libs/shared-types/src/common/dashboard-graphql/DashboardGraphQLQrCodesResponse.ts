import { DashboardGraphQLPaginationResponseInterface, DashboardGraphQLScalars, DashboardGraphQLQrCode, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLQrCodesResponse = DashboardGraphQLPaginationResponseInterface & {
  __typename?: 'QRCodesResponse';
  limit: DashboardGraphQLScalars['PositiveInt'];
  offset: DashboardGraphQLScalars['NonNegativeInt'];
  qrCodes: Array<DashboardGraphQLQrCode>;
  total?: DashboardGraphQLMaybe<DashboardGraphQLScalars['NonNegativeInt']>;
};