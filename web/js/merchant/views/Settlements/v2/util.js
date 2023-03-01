import { isOrgFeatureExist } from 'merchant/models/User';
import { merchantFetch } from 'merchant/utils/ajax';
import { SelfServeActionPages } from 'common/constant/enums';

export const calculateCreditDebitAmount = (items, isBreakupNew) => {
  return items.reduce(
    (acc, item) => {
      if (isBreakupNew) {
        if (item.type === 'credit') acc.credit = acc.credit + item.settled_amount;
        if (item.type === 'debit') acc.debit = acc.debit + item.settled_amount;
      } else {
        if (item.type === 'credit') acc.credit = acc.credit + item.amount;
        if (item.type === 'debit') acc.debit = acc.debit + item.amount;
      }
      return acc;
    },
    { credit: 0, debit: 0 },
  );
};

export const sanitizeTabName = (tabName) => tabName.split('_')[0].trim();

/**
 * For single recon we have Unreconciled breakup for external transactions, no need to show in entities table
 * @param {object} items array of items breakup details
 * @returns {object} filtered items for unreconciled entity
 */
export const removeUnreconciledEntity = (items) =>
  items.filter((item) => item.component !== 'unreconciled');

export const fetchBankSettleStatus = (settlementId) => {
  return merchantFetch({
    url: `org_settlements/${settlementId}`,
    method: 'GET',
  });
};

export const customSettlementEnabled = (user) => {
  const isOrgSettleToBank = isOrgFeatureExist('org_settle_to_bank');
  const isCancelSeettleToBank = user?.isFeatureEnabled('cancel_settle_to_bank');
  const isOldCustomSettleFlow = user?.isFeatureEnabled('old_custom_settl_flow');
  return isOrgSettleToBank && !isCancelSeettleToBank && !isOldCustomSettleFlow;
};

export const getSelfServeDetailForSettlementDetails = (source = 'payment') => {
  let selfServeActionName = '';
  let INIT_POINT = '';
  let INIT_PAGE = '';
  let page = '';

  if (source === 'payment' || source === 'payment_domestic' || source === 'payment_international') {
    selfServeActionName = 'Payment Details Fetched';
    INIT_POINT = 'payments-table';
    INIT_PAGE = SelfServeActionPages.SettlementsPayments;
    page = 'Payments';
  } else if (source === 'refund') {
    selfServeActionName = 'Refund Details Fetched';
    INIT_POINT = 'refunds-table';
    INIT_PAGE = SelfServeActionPages.SettlementsRefunds;
    page = 'Refunds';
  } else if (source === 'reversal') {
    INIT_POINT = 'reversals-table';
    INIT_PAGE = SelfServeActionPages.SettlementsReversals;
    page = 'Reversals';
  } else if (source === 'transfer') {
    INIT_POINT = 'transfers-table';
    INIT_PAGE = SelfServeActionPages.SettlementsTransfers;
    page = 'Transfers';
  }

  return { selfServeActionName, page, INIT_POINT, INIT_PAGE };
};
