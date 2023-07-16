import { generateSampleData } from 'merchant/views/MagicCheckout/ShopifyOrderEditing/mocks/helper';

let sampleLineItems = [
  ...generateSampleData(3),
  {
    name: 'New Year Gift Card',
    quantity: 1,
    currentQuantity: 1,
    image_url: '',
    variant_name: 'New Year Gift Card',
    variant_id: `gid://shopify/LineItem/12345`,
    variant_price: 500,
    id: 459,
  },
];
// Intentioally not used msw here, because the setup for msw is not available for browser level network requests, and only nodejs level requests. Will move this under mocks as msw requests for writing the UTs, and this file will be deleted, since its created only because BE is not ready for my testing.
export const shopifyOrderEditingHandlers = {
  getOrder: (): any => {
    return {
      id: 'shopify_order_id',
      amount: '200',
      discount: '10',
      shipping_charges: '20',
      total_amount: '230',
      customer: {
        name: 'XYZ',
        contact: '+91000000000',
        billing_address: {
          firstName: 'xyz',
          address1: '23456789',
          address2: 'dfghjkl',
          city: 'Bengaluru',
          country: 'India',
          zipcode: '560001',
          countryCodeV2: 'IN',
        },
        shipping_address: {},
      },
      line_items: sampleLineItems,
    };
  },
  getProducts: ({
    limit = 10,
    offset = 0,
    searchTerm = '',
  }: {
    limit?: number;
    offset?: number;
    searchTerm?: string;
  }): any => {
    const data = generateSampleData(30);

    const filteredData = searchTerm
      ? data.filter((item) => item.variant_name.toLowerCase().includes(searchTerm.toLowerCase()))
      : data;

    const paginatedData = filteredData.slice(Number(offset), Number(offset) + Number(limit));

    return {
      data: paginatedData,
      total: filteredData.length,
    };
  },
  deleteItem: (variantId: string): any => {
    sampleLineItems = sampleLineItems.filter((item) => item.id !== Number(variantId));

    return {
      id: 'shopify_order_id',
      amount: '200',
      discount: '10',
      shipping_charges: '20',
      total_amount: '230',
      customer: {
        name: 'XYZ',
        contact: '+91000000000',
        billing_address: {
          firstName: 'xyz',
          address1: '23456789',
          address2: 'dfghjkl',
          city: 'Bengaluru',
          country: 'India',
          zipcode: '560001',
          countryCodeV2: 'IN',
        },
        shipping_address: {},
      },
      line_items: sampleLineItems,
    };
  },
};
