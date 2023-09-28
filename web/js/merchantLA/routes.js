import store from 'merchantLA/store';

import { matchDetail as matchDetailx, matchModal as matchModalx } from 'merchant_common/routes';

import SettlementDetails from 'merchantLA/containers/Settlements/Details';
import ReversalDetails from 'merchantLA/containers/Marketplace/Reversals/Details';
import LARefundsBatchDetails from 'merchantLA/containers/Marketplace/Reversals/BatchDetails';
import TransferDetails from 'merchantLA/containers/Marketplace/Transfers/Details';

/*
 * NOTE: entityDetailsMap and entityModalsMap must be mutually exclusive sets
 * */

const entityDetailsMap = {
  '/transfers/:id(trf_.+)/:reversal_id(rvrsl_.+)': {
    component: TransferDetails,
  },
  '/transfers/:id(trf_.+)': { component: TransferDetails },
  '/settlements/:id': { component: SettlementDetails },
  '/reversals/:id(rvrsl_.+)': { component: ReversalDetails },
  '/reversals/batchuploads/:id(batch_.+)': {
    component: LARefundsBatchDetails,
  },
};

/*
 * Example:
 * - '/{route_name}[/{route_sub_path}]': {component: ComponentName, featureEnabled: "whatFeature", featureEnabled: "whatFeature"}
 * */
const entityModalsMap = {};

export const matchDetail = matchDetailx(store, entityDetailsMap);
export const matchModal = matchModalx(store, entityModalsMap);
