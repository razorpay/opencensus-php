import { isOrgFeatureExist } from 'merchant/models/User';
import { merchantFetch } from 'merchant/utils/ajax';
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
