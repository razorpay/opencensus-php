import { getAmountFieldTypes } from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/helpers/index';
describe('Amount Field', () => {
  test('should return true if dynamic_price is there', () => {
    const amountList = getAmountFieldTypes();
    const dynamicPriceMissing = amountList.every((obj) => {
      return obj.key !== 'dynamic_price';
    });
    expect(!dynamicPriceMissing).toBe(true);
  });

  test('should return true if dynamic_price is missing', () => {
    const amountList = getAmountFieldTypes(true);
    const dynamicPriceMissing = amountList.every((obj) => {
      return obj.key !== 'dynamic_price';
    });
    expect(dynamicPriceMissing).toBe(true);
  });
});
