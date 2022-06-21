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
