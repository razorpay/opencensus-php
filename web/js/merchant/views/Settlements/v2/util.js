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
