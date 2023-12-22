import { isFormItemOfTypeLateFee } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers';

export function getTotalPriceItems(priceItems) {
  return priceItems.reduce(
    (acc, fi) => {
      const isLateFeeField = isFormItemOfTypeLateFee(fi);

      if (isLateFeeField) {
        acc.totalLateFeeItems += 1;
      } else {
        acc.totalAmountItems += 1;
      }

      return acc;
    },
    { totalAmountItems: 0, totalLateFeeItems: 0 },
  );
}
