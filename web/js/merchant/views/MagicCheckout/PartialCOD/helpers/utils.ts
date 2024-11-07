import {
  CustomerRiskCategory,
  PREPAID_PAYMENY_AMOUNT_ITEM_TYPE,
  PrepaidPaymentAmountItem,
} from 'merchant/views/MagicCheckout/PartialCOD/types';
import { i18nifyConvertToMajorUnit } from 'merchant/views/Transactions/v2/common/utils';

export const getSlabCustomerRiskText = (customerRiskCategory: CustomerRiskCategory[]) => {
  if (Array.isArray(customerRiskCategory))
    return customerRiskCategory.length >= 3
      ? 'Any'
      : customerRiskCategory
          .map((string) => string.charAt(0).toUpperCase() + string.slice(1))
          .join(' and ');

  return '';
};

export const initializeSlabData = (data: PrepaidPaymentAmountItem) => ({
  ...data,
  value: String(
    data.type === PREPAID_PAYMENY_AMOUNT_ITEM_TYPE.FLAT
      ? i18nifyConvertToMajorUnit(data.value)
      : data.value,
  ),
  rules: {
    ...data.rules,
    min_order_amount: String(i18nifyConvertToMajorUnit(data.rules.min_order_amount)),
    max_order_amount: data.rules.max_order_amount
      ? String(i18nifyConvertToMajorUnit(data.rules.max_order_amount))
      : undefined,
  },
});
