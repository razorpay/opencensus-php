import { COLLECTIONS_BALANCE_TYPE } from '../../constants';

export const getPrincipalAmount = ({
  isCurrentOutstandingRepayType,
  currentOutstandingPrincipalAmount,
  isTotalOwedRepayType,
  totalPrincipalAmount,
}) => {
  if (isCurrentOutstandingRepayType) return currentOutstandingPrincipalAmount;
  else if (isTotalOwedRepayType) return totalPrincipalAmount;
  else return 0;
};

export const getInterestAmount = ({
  isCurrentOutstandingRepayType,
  currentOutstandingInterestAmount,
  isTotalOwedRepayType,
  totalInterestAmount,
}) => {
  if (isCurrentOutstandingRepayType) return currentOutstandingInterestAmount;
  else if (isTotalOwedRepayType) return totalInterestAmount;
  else return 0;
};

export const getTotalAmountBreakup = (balances) => {
  const totalAmountBreakup = {
    totalInterestAmount: 0,
    totalPrincipalAmount: 0,
  };
  if (!balances || !balances.data || !balances.data.length) return totalAmountBreakup;
  return balances.data.reduce((amountBreakup, { balance_type, balance_amount }) => {
    switch (balance_type) {
      case COLLECTIONS_BALANCE_TYPE.BALANCE_TYPE_INTEREST:
        amountBreakup.totalInterestAmount += Number(balance_amount);
        return amountBreakup;
      case COLLECTIONS_BALANCE_TYPE.BALANCE_TYPE_PRINCIPAL:
        amountBreakup.totalPrincipalAmount += Number(balance_amount);
        return amountBreakup;
      default:
        return amountBreakup;
    }
  }, totalAmountBreakup);
};

export const getNextRepayBreakup = (installments) => {
  const nextRepayBreakup = {
    nextRepayInterestAmount: 0,
    nextRepayPrincipalAmount: 0,
    nextRepaymentDate: null,
  };
  if (!installments || !installments.data || !installments.data.length) return nextRepayBreakup;
  for (const installment of installments.data) {
    if (
      !installment.hasOwnProperty('principal_collected') &&
      !installment.hasOwnProperty('interest_collected')
    ) {
      nextRepayBreakup.nextRepayInterestAmount = Number(installment.interest);
      nextRepayBreakup.nextRepayPrincipalAmount = Number(installment.principal);
      nextRepayBreakup.nextRepaymentDate = installment.repayment_date;
      return nextRepayBreakup;
    } else {
      const principalCollected = Number(installment.principal_collected) || 0;
      const interestCollected = Number(installment.interest_collected) || 0;
      const amountCollected = principalCollected + interestCollected;
      const installmentTotalPayment = Number(installment.payment) || 0;
      const installmentPrincipal = Number(installment.principal) || 0;
      const installmentInterest = Number(installment.interest) || 0;
      if (installmentTotalPayment > amountCollected) {
        nextRepayBreakup.nextRepayInterestAmount = installmentInterest - interestCollected;
        nextRepayBreakup.nextRepayPrincipalAmount = installmentPrincipal - principalCollected;
        nextRepayBreakup.nextRepaymentDate = installment.repayment_date;
        return nextRepayBreakup;
      }
    }
  }
  return nextRepayBreakup;
};

export const getCurrentOutstandingBreakup = (currentOutstanding) => {
  const currentOutstandingBreakup = {
    total: 0,
    principal: 0,
    interest: 0,
  };

  const {
    payment = 0,
    principal_collected = 0,
    interest_collected = 0,
    principal = 0,
    interest = 0,
  } = currentOutstanding?.data || {};

  currentOutstandingBreakup.total =
    Number(payment) - Number(principal_collected) - Number(interest_collected);
  currentOutstandingBreakup.principal = Number(principal) - Number(principal_collected);
  currentOutstandingBreakup.interest = Number(interest) - Number(interest_collected);
  return currentOutstandingBreakup;
};
