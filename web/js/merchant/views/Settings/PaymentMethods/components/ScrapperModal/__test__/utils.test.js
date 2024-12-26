import { hasValidFieldsForCategory } from 'merchant/views/Settings/PaymentMethods/components/ScrapperModal/utils';
const data = [
  {
    name: 'merchant_details|business_model',
    display_name: 'Business model description',
    type: 'textarea',
    category: 'Business Details',
    value: 'E-commerce platform',
  },
  {
    name: 'merchant_details|business_description',
    display_name: 'Business description',
    type: 'text',
    category: 'Business Details',
    value: 'Business XYZ',
  },
  {
    name: 'merchant_business_detail|website_details|pricing',
    display_name: 'pricing',
    type: 'text',
    category: 'Website Details',
    value: 'razorpay.com/pricing',
  },
  {
    name: 'merchant_business_detail|website_details|terms',
    display_name: 'Terms & Conditions Policy link',
    type: 'text',
    category: 'Website Details',
    value: 'razorpay.com/terms',
  },
  {
    name: 'merchant_business_detail|website_details|privacy',
    display_name: 'Privacy Policy',
    type: 'text',
    category: 'Website Details',
    value: '', // Empty value
  },
  {
    name: 'merchant_business_detail|website_details|cancellation',
    display_name: 'Cancellation Policy',
    type: 'date',
    category: 'Website Details',
    value: new Date('2024-10-15'),
  },
  {
    name: 'merchant_business_detail|website_details|refund',
    display_name: 'Refund Policy',
    type: 'date',
    category: 'Website Details',
    value: new Date(),
  },
];

describe('hasValidFieldsForCategory', () => {
  test('should return true when all fields in the category have valid values', () => {
    const result = hasValidFieldsForCategory(data, 'Business Details');
    expect(result).toBe(true);
  });

  test('should return false when a field in the category has an empty string value', () => {
    const result = hasValidFieldsForCategory(data, 'Website Details');
    expect(result).toBe(false);
  });

  test('should return true when all fields in the category have non-empty strings', () => {
    // Clone the data array to avoid mutation
    const clonedData = JSON.parse(JSON.stringify(data));

    clonedData.find(
      (item) => item.name === 'merchant_business_detail|website_details|privacy',
    ).value = 'razorpay.com/privacy';

    const result = hasValidFieldsForCategory(clonedData, 'Website Details');

    expect(result).toBe(true);
  });
});
