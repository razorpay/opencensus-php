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
  items: { tax: number; fees: number; amount: number }[];
};
export const platformFeeCalculator = ({
  fee,
  tax,
  amount_transferred,
  loading,
  items,
}: platformFeeCalculatorProps): platformFeeCalculatorReturnType => {
  const totalFeeAmount = fee + tax + amount_transferred;
  let totalRazorpayFee = tax + fee;
  let totalFee = fee;
  let totalTax = tax;
  let platformFee = 0;

  if (!loading && items.length > 0) {
    items.forEach((item) => {
      totalRazorpayFee += item.tax + item.fees;
      totalFee += item.fees;
      totalTax += item.tax;
      platformFee += item.amount;
    });
  }
  return { totalFeeAmount, totalFee, totalRazorpayFee, totalTax, platformFee };
};

const PLATFORM = 'platform';
interface isPlatformTransactionProps {
  loading: boolean;
  items: { transfer_type?: string }[];
}
export const isPlatformTransaction = (transfer: isPlatformTransactionProps): boolean => {
  const { loading: isLoading, items } = transfer;
  if (!isLoading && items.length > 0) {
    const firstValue = items[0]?.transfer_type; // Get the value of the field for the first object
    if (firstValue !== PLATFORM) {
      return false;
    } else {
      for (let i = 1; i < items.length; i++) {
        if (items[i]?.transfer_type !== firstValue) {
          return false; // If any value is different, return false
        }
      }
      return true; // If all values are the same, return true
    }
  }
  return false;
};
