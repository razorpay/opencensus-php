type platformFeeCalculatorReturnType = {
  totalFeeAmount: number;
  totalFee: number;
  totalPaymentFee: number;
  totalTax: number;
  partnerFee: number;
};
type platformFeeCalculatorProps = {
  fee: number;
  tax: number;
  amount_transferred: number;
  loading: boolean;
  items: { tax: number; fees: number; amount: number; amount_reversed: number }[];
  isPartnerPlatformFeeEnabled: boolean;
};
export const platformFeeCalculator = ({
  fee,
  tax,
  loading,
  items,
  isPartnerPlatformFeeEnabled,
}: platformFeeCalculatorProps): platformFeeCalculatorReturnType => {
  let totalFeeAmount = fee;
  const totalPaymentFee = fee;
  const totalFee = fee - tax;
  const totalTax = tax;
  let partnerFee = 0;

  if (!loading && items.length > 0) {
    items.forEach((item) => {
      if (isPartnerPlatformFeeEnabled) {
        totalFeeAmount += item.fees + item.amount - item.amount_reversed;
        partnerFee += item.amount + item.fees - item.amount_reversed;
      } else {
        totalFeeAmount += item.fees;
        partnerFee += item.amount - item.amount_reversed;
      }
    });
  }
  return { totalFeeAmount, totalFee, totalPaymentFee, totalTax, partnerFee };
};

interface isPlatformTransactionProps {
  loading: boolean;
  items: { partner_details?: { name: string }; id?: string }[];
}
export const isPlatformTransaction = (transfer: isPlatformTransactionProps): boolean => {
  const { loading: isLoading, items } = transfer;
  if (!isLoading && items.length) {
    for (let i = 0; i < items.length; i++) {
      if (!items[i].partner_details) {
        return false; // check whether all the items of the array meets the condition
      }
    }
    return true;
  }
  return false;
};
