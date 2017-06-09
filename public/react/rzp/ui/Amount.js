import { getFormattedAmount } from 'rzp/utils/rzp-utils';

export default ({ value, currency, ...attrs }) => {
  return (
    <span {...attrs}>
      ₹ {getFormattedAmount(value)}
    </span>
  );
};
