import { DashboardGraphQLMerchantAcceptanceChannel, DashboardGraphQLMerchantAcceptanceChannelWhatsappSmsEmail } from './index';
export type DashboardGraphQLMerchantPaymentAcceptanceChannels = {
  __typename?: 'DashboardGraphQLMerchantPaymentAcceptanceChannels';
  android: DashboardGraphQLMerchantAcceptanceChannel;
  ios: DashboardGraphQLMerchantAcceptanceChannel;
  offlineStore: DashboardGraphQLMerchantAcceptanceChannel;
  others: DashboardGraphQLMerchantAcceptanceChannel;
  socialMedia: DashboardGraphQLMerchantAcceptanceChannel;
  websites: DashboardGraphQLMerchantAcceptanceChannel;
  whatsappSmsEmail: DashboardGraphQLMerchantAcceptanceChannelWhatsappSmsEmail;
};