import { convertToMajorUnit, convertToMinorUnit } from '@razorpay/i18nify-js';
import moment from 'moment';

import { validateAmount } from 'common/utils/validators';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import { IPaymentDetails } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/types';

export const calculateHasEnoughFunds = ({
  payment,
  user,
  currentBalance,
  isInstantRefundChecked,
  payableAmount,
}) => {
  if (!payment || !user || !currentBalance) {
    return false;
  }
  const { data } = currentBalance;
  // if this flag is true and they opt for normal refund, we skip balance check validations
  if (payment.direct_settlement_refund && !isInstantRefundChecked) {
    return true;
  }
  const merchant = user.merchants[user.current] || {};
  const isBalanceSource = merchant.refund_source === 'balance';

  const amount = convertToMinorUnit(payableAmount, { currency: payment.currency });

  let balance = isBalanceSource ? data?.balance : data?.refund_credits;
  if (user.isRefundSourceFallbackEnabled) {
    balance = Math.max(data?.balance || 0, data?.refund_credits || 0);
  }

  if (currentBalance?.loading) {
    return true;
  }

  if (balance) {
    return amount <= balance;
  }

  return false;
};

export const getLabelForRefundDefaultSpeed = (defaultRefundSpeed) => {
  return defaultRefundSpeed === 'optimum' ? 'optimum' : 'normal';
};
export const getMonthsFromDays = (value) => Math.round(moment.duration(value, 'days').asMonths());

export class PaymentUtils {
  private payment: IPaymentDetails;

  constructor(payment: IPaymentDetails) {
    this.payment = payment;
    this.isRefundButtonDisabled = this.isRefundButtonDisabled.bind(this);
    this.isPartialPayment = this.isPartialPayment.bind(this);
    this.amountValidation = this.amountValidation.bind(this);
    this.getRefundFeeForPayableAmount = this.getRefundFeeForPayableAmount.bind(this);
    this.nonFraudDisputeCount = this.nonFraudDisputeCount.bind(this);
  }

  isRefundButtonDisabled({
    payableAmount,
    isInstantRefundChecked,
    isRefundApiInProgress,
  }): boolean {
    return (
      (!this.payment?.gateway_refund_support && !this.payment?.instant_refund_support) ||
      !payableAmount ||
      (!isInstantRefundChecked && !this.payment?.gateway_refund_support) ||
      isRefundApiInProgress
    );
  }

  isPartialPayment(payableAmount): boolean {
    const refundableAmount = this.payment?.amount - this.payment?.amount_refunded;
    const amountEntered = convertToMinorUnit(payableAmount, { currency: this.payment.currency });
    return amountEntered < refundableAmount;
  }

  getRefundFeeForPayableAmount = (amount, getRefundFee) => {
    const current_payable_amount = convertToMinorUnit(amount, { currency: this.payment.currency });
    if (
      current_payable_amount &&
      current_payable_amount >= 100 &&
      !showWhenUtil({ featureEnabled: 'disable_instant_refunds' }) &&
      current_payable_amount <= this.payment.amount
    ) {
      if (this.payment.instant_refund_support) {
        getRefundFee(amount);
      }
    }
  };

  amountValidation(amount): string {
    const value = amount || '';
    if (!value) return 'Amount is required';
    if (value < 1 && this.payment.currency === 'INR') return `Amount can't be less than 1`;

    const amountError = validateAmount(value, null, this.payment.currency);
    if (amountError) return amountError;

    const refundableAmount = this.payment.amount - this.payment.amount_refunded;
    if (convertToMinorUnit(value, { currency: this.payment.currency }) > refundableAmount) {
      return `Amount can't be greater than the total Refundable Amount (${convertToMajorUnit(
        refundableAmount,
        { currency: this.payment.currency },
      )}).`;
    }
    return '';
  }

  nonFraudDisputeCount(): number {
    return (
      this.payment.disputes &&
      this.payment.disputes.items.filter((dispute) => dispute.phase !== 'fraud').length
    );
  }
}
