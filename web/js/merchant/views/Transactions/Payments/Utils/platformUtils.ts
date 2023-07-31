type platformFeeCalculatorReturnType = {
  totalFeeAmount: number;
  totalFee: number;
  totalRazorpayFee: number;
  totalTax: number;
  platformFee: number;
};
type platformFeeCalculatorProps = {
  fee: number;
  tax: number;
  amount_transferred: number;
  loading: boolean;
  items: { tax: number; fees: number; amount: number; amount_reversed: number }[];
};
export const platformFeeCalculator = ({
  fee,
  tax,
  loading,
  items,
}: platformFeeCalculatorProps): platformFeeCalculatorReturnType => {
  let totalFeeAmount = fee;
  const totalRazorpayFee = fee;
  const totalFee = fee - tax;
  const totalTax = tax;
  let platformFee = 0;

  if (!loading && items.length > 0) {
    items.forEach((item) => {
      totalFeeAmount += item.fees + item.amount - item.amount_reversed;
      platformFee += item.amount + item.fees - item.amount_reversed;
    });
  }
  return { totalFeeAmount, totalFee, totalRazorpayFee, totalTax, platformFee };
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
