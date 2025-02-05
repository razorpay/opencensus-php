import {
  generateProductRequest,
  shouldShowStrikethrough,
} from 'merchant/views/PaymentPages/common/Products/utils';

const product = {
  id: 'my-id',
  product_name: 'dummy product',
  description: 'my description',
  amount: '1000',
  discounted_amount: '500',
  units: '10',
  images: [],
  category: null,
};
const product2 = {
  id: 'my-id',
  product_name: 'dummy product',
  description: 'my description',
  amount: '1000',
  discounted_amount: '',
  units: '',
  images: [],
  category: 'cat_KzCUxOrkFBr0UP',
};
const product3 = {
  id: 'my-id',
  product_name: 'dummy product',
  description: 'my description',
  amount: '1000',
  discounted_amount: '',
  units: '0',
  images: [],
  category: null,
};

describe('generateProductRequest', () => {
  test('should send the basic fields', () => {
    const request = generateProductRequest(product);
    expect(request.product_name).toEqual(product.product_name);
    expect(request.description).toEqual(product.description);
    expect(request.currency).toEqual('INR');
  });
  describe('generateProductRequest -> price fields', () => {
    test('should send price fields in paise ', () => {
      const request = generateProductRequest(product);
      expect(request.amount).toBe(Number(product.amount) * 100);
      expect(request.discounted_amount).toBe(Number(product.discounted_amount) * 100);
    });
    test('should not send discounted price if its empty', () => {
      const request = generateProductRequest(product2);
      expect(request.discounted_amount).toEqual(undefined);
    });
  });

  describe('generateProductRequest -> status/units', () => {
    test('should send in_stock if units >= 1', () => {
      const request = generateProductRequest(product);
      expect(request.status).toEqual('in_stock');
      // convert to number
      expect(request.units).toEqual(Number(product.units));
    });
    test('should send unlimited if units is empty', () => {
      const request = generateProductRequest(product2);
      expect(request.status).toEqual('unlimited');
      // dont send units
      expect(request.units).toEqual(undefined);
    });
    test('should send out_of_stock if units is 0', () => {
      const request = generateProductRequest(product3);
      expect(request.status).toEqual('out_of_stock');
      // convert to number
      expect(request.units).toEqual(Number(product3.units));
    });
  });
  describe('generateProductRequest -> category', () => {
    test('empty category', () => {
      const request = generateProductRequest(product);
      // dont send the key
      expect(request.categories).toEqual(undefined);
    });
    test('should send selected category', () => {
      const request = generateProductRequest(product2);
      expect(request.categories && request.categories[0].id).toEqual(product2.category);
    });
  });

  describe('shouldShowStrikethrough', () => {
    test('should return false if discountedAmount is empty', () => {
      expect(shouldShowStrikethrough('', '1000')).toBe(false);
    });

    test('should return false if discountedAmount is greater than or equal to amount', () => {
      expect(shouldShowStrikethrough('1000', '1000')).toBe(false);
      expect(shouldShowStrikethrough('1200', '1000')).toBe(false);
    });

    test('should return true if discountedAmount is less than amount', () => {
      expect(shouldShowStrikethrough('500', '1000')).toBe(true);
    });

    test('should handle discountedAmount as a string of numeric value', () => {
      expect(shouldShowStrikethrough('500.5', '1000')).toBe(true);
    });

    test('should return false if both discountedAmount and amount are zero', () => {
      expect(shouldShowStrikethrough('0', '0')).toBe(false);
    });
  });
});
