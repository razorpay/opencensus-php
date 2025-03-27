import { FALLBACK_PRODUCTS, getFallbackProductsForConnectedNav } from 'merchant/components/SidebarV2/utils/Fallback';

describe('SidebarV2 utils -> Fallback', () => {
  test('should return the correct fallback products for connected nav', () => {
    const result = getFallbackProductsForConnectedNav({ isFeatureEnabled: jest.fn(() => false) });
    expect(result).toEqual(FALLBACK_PRODUCTS);
  });

  test('should return hosted optimizer product if feature is enabled', () => {
    const result = getFallbackProductsForConnectedNav({ isFeatureEnabled: jest.fn(() => true) });
    const hostedOptimizerResult = result[0].product_options.find((product) => product.product_id === 'optimizer');
    expect(hostedOptimizerResult).toEqual({
      title: 'Hosted Optimizer',
      product_id: 'optimizer',
      category: '',
      tags: [],
    });
  });
});
