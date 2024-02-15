import {
  FetchSubmerchantsParams,
  fetchSubmerchants,
  PGAcceptedInviteItem,
  FetchInviteResponse,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/api';
import {
  getActivationStatusBulk,
  getFormattedCapitalResponse,
} from 'merchant/views/PartnerDashboard/SubMerchant/utils/activationStatusHelper';

export { FetchSubmerchantsParams, fetchSubmerchants, PGAcceptedInviteItem, FetchInviteResponse };
export interface CapitalAcceptedInviteItem extends PGAcceptedInviteItem {
  capitalActivationStatus?: string;
}
type LoanApplicationDetailsProduct = {
  id: string;
  name: string;
};
export type LoanApplicationDetailsProducts = {
  loading?: boolean;
  data?: Array<LoanApplicationDetailsProduct>;
};
type getActivationBulkDataArgs = {
  items: Array<PGAcceptedInviteItem>;
  products: LoanApplicationDetailsProducts;
};

export const getActivationBulkData = async ({
  items,
  products,
}: getActivationBulkDataArgs): Promise<Array<CapitalAcceptedInviteItem>> => {
  const product = products.data?.filter((item) => {
    return item.name === 'LOC_EMI';
  });
  const productId = product?.[0]?.id as string;
  const merchantIds = items.map((item) => item.id.replace('acc_', ''));
  const response = await getActivationStatusBulk(merchantIds, productId);
  if (response?.data) {
    const { data } = response;
    const formattedData = getFormattedCapitalResponse(data, items);
    return formattedData;
  } else {
    return items;
  }
};
