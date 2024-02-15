import { fetchInvites } from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/api';

// Re-exports from legacy code
export {
  FetchInvitesParams,
  SubmerchantInviteItem,
  FetchInviteResponse,
  fetchInvites,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/AllInvitesTable/api';

export const checkIfAllInvitesEmpty = async (
  partnerId: string,
  productType: string,
): Promise<boolean> => {
  // Note: This is a partially nonblocking network call to determine the welcome screen condition

  const data = await fetchInvites(partnerId, {
    product: productType,
    skip: 0,
    count: 1,
  });
  return (data.data?.items || []).length === 0;
};
