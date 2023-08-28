import React from 'react';

import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';

import BulkAddMerchantCapital from './BulkAddMerchantCapital';
import BulkAddMerchantPG from './BulkAddMerchantPG';
import BulkAddMerchantX from './BulkAddMerchantX';

type BulkAddMerchantProps = {
  productType: string;
  onInviteTabsBackClick: () => void;
  onDismiss: () => void;
  onAddSuccess?: () => void;
};
const BulkAddMerchant = ({ productType, ...props }: BulkAddMerchantProps): JSX.Element => {
  let Component = BulkAddMerchantPG;
  switch (productType) {
    case PRODUCT_TYPE.CAPITAL:
      Component = BulkAddMerchantCapital;
      break;
    case PRODUCT_TYPE.X:
      Component = BulkAddMerchantX;
      break;
    case PRODUCT_TYPE.PG:
    default:
      Component = BulkAddMerchantPG;
      break;
  }
  return <Component {...props} />;
};
export default BulkAddMerchant;
