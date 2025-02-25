import { DashboardGraphQLInputMaybe, DashboardGraphQLMerchantAcceptanceChannelInput, DashboardGraphQLMerchantAcceptanceChannelWhatsappSmsEmailInput } from './index';
export type DashboardGraphQLMerchantPaymentAcceptanceChannelsInput = {
  android?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantAcceptanceChannelInput>;
  ios?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantAcceptanceChannelInput>;
  offlineStore?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantAcceptanceChannelInput>;
  others?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantAcceptanceChannelInput>;
  socialMedia?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantAcceptanceChannelInput>;
  websites?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantAcceptanceChannelInput>;
  whatsappSmsEmail?: DashboardGraphQLInputMaybe<DashboardGraphQLMerchantAcceptanceChannelWhatsappSmsEmailInput>;
};