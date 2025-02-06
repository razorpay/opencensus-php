import * as LocalStorageUtils from 'common/utils/localStorage';
import {
  MOCK_ADDRESSES,
  MOCK_PRICING_WITH_PRICES,
  MOCK_PRODUCT,
  MOCK_USER,
  MOCK_PRODUCT_PRICING_RESPONSE,
  MOCK_PRODUCT_OFFER_CONFIG,
  MOCK_PRICING_WITH_ONLY_LIFETIME_PLAN,
} from 'merchant/views/POS/__tests__/mocks/fixtures';
import { UPDATE_CART_ACTIONS } from 'merchant/views/POS/constants';
import {
  getCartFromLocalStorage,
  getCartItemTotal,
  saveCartInBrowserStorage,
  getProductDescriptionWithPricingPlan,
  constructProductDescription,
  getProductFromCart,
  updateCart,
  initializeDeliveryAddresses,
  isValidAddresses,
  updateDeliveryAddress,
  saveAddressInLocalStorage,
  getAllDeliveryAddressFromLocalStorage,
  processPrecheckoutPricing,
  isPosExperimentEnabled,
  getOrderQuantityError,
  isItemPresentInCart,
  getAvailablePricingPlans,
  getInitialPlan,
} from 'merchant/views/POS/helpers';
import { CartItem, PricingTypes, ProductDescription, ProductPlans } from 'merchant/views/POS/types';

const cartItems = [
  {
    code: 'mock-product',
    quantity: 3,
    plan: 'monthly' as PricingTypes,
  },
  {
    code: 'mock-product',
    quantity: 3,
    plan: 'lifetime' as PricingTypes,
  },
];

const PROUDCT = {
  code: 'mock-product',
  quantity: 3,
  plan: 'monthly' as ProductPlans,
};

describe('helpers', () => {
  beforeEach(() => jest.resetAllMocks());

  test('should return cart item total if correct params passed to getCartItemTotal', () => {
    const total = getCartItemTotal({
      pricing: MOCK_PRICING_WITH_PRICES,
      quantity: 3,
      selectedPlan: 'lifetime',
    });
    expect(total.value).toBe(36000);
  });

  test('should return cart item total if correct params passed to getCartItemTotal and only consider isChargeableAtCheckout', () => {
    const total = getCartItemTotal({
      pricing: MOCK_PRICING_WITH_PRICES,
      quantity: 1,
      selectedPlan: 'monthly',
    });
    expect(total.value).toBe(1200);
  });

  test('saveCartInBrowserStorage should trigger store to localstorage with correct cart item', () => {
    const localstorageUtilSpy = jest.spyOn(LocalStorageUtils, 'setItem');
    const cartItems = [
      PROUDCT,
      {
        ...PROUDCT,
        code: 'fake-product',
      },
    ];
    saveCartInBrowserStorage({ cartItems, userId: 'mock-user-id' });
    expect(localstorageUtilSpy).toHaveBeenCalledWith(
      'pos-user-mock-user-id-cart',
      JSON.stringify([PROUDCT]),
    );
  });

  test('getCartFromLocalStorage should return cart items from localstorage', () => {
    const cartItems = [
      PROUDCT,
      {
        ...PROUDCT,
        code: 'fake-product',
      },
    ];
    const localStorageSpy = jest.spyOn(Storage.prototype, 'getItem');
    localStorageSpy.mockReturnValue(JSON.stringify(cartItems));
    const retainedCartItems = getCartFromLocalStorage({ userId: 'mock-user-id' });
    expect(retainedCartItems).toStrictEqual([PROUDCT]);
  });

  test('getProductDescriptionWithPricingPlan should return product description with pricing plan', () => {
    const description = getProductDescriptionWithPricingPlan({
      productCode: 'mock-product',
      pricingPlanDict: MOCK_PRODUCT_PRICING_RESPONSE,
    });
    const monthlyPlan = description?.pricing.find((pricing) => pricing.type === 'monthly');
    const { breakups } = monthlyPlan ?? {};
    expect(breakups?.[0].value).toBe(300);
  });

  test('getProductDescriptionWithPricingPlan should return product description with offers if exits', () => {
    const description = getProductDescriptionWithPricingPlan({
      productCode: 'mock-product',
      pricingPlanDict: MOCK_PRODUCT_PRICING_RESPONSE,
      offerConfigForProduct: MOCK_PRODUCT_OFFER_CONFIG['mock-product'],
    });
    expect(description?.offer?.offerText).toBe('Mock offer text');
  });

  test('getProductDescriptionWithPricingPlan should through error if pricing plan not found', () => {
    const payload = {
      productCode: 'fake-product',
      pricingPlanDict: MOCK_PRODUCT_PRICING_RESPONSE,
    };
    expect(getProductDescriptionWithPricingPlan(payload)).toBeNull();
  });

  test('constructProductDescription should return product description with pricing plan', () => {
    const productDescriptions = constructProductDescription({
      pricingPlanDict: MOCK_PRODUCT_PRICING_RESPONSE,
    });
    expect(productDescriptions.length).toBe(2);
  });
});

describe('Cart helpers', () => {
  test('getProductFromCart should render correct cart item as identified by productName and plan', () => {
    const cartItem = getProductFromCart({
      cart: cartItems,
      product: { productCode: 'mock-product', plan: 'monthly' },
    });
    expect(cartItem).toStrictEqual({
      code: 'mock-product',
      quantity: 3,
      plan: 'monthly',
    });
  });

  test('should add to cart if update cart called with ADD_TO_CART action', () => {
    const updatedCart = updateCart({
      cart: [],
      type: UPDATE_CART_ACTIONS.ADD_TO_CART,
      product: {
        productCode: 'mock-product',
        plan: 'monthly',
      },
    });
    expect(updatedCart).toStrictEqual([
      {
        code: 'mock-product',
        quantity: 1,
        plan: 'monthly',
      },
    ]);
  });

  test('should increase cart quantity if updateCart is called with INCREASE_QUANTITY action', () => {
    const updatedCart = updateCart({
      cart: cartItems,
      type: UPDATE_CART_ACTIONS.INCREASE_QUANTITY,
      product: {
        productCode: 'mock-product',
        plan: 'monthly',
      },
    });
    const cartItem = getProductFromCart({
      cart: updatedCart,
      product: { productCode: 'mock-product', plan: 'monthly' },
    });
    expect(cartItem?.quantity).toBe(4);
  });

  test('should decrease cart quantity if updateCart is called with DECREASE_QUANTITY action', () => {
    const updatedCart = updateCart({
      cart: cartItems,
      type: UPDATE_CART_ACTIONS.DECREASE_QUANTITY,
      product: {
        productCode: 'mock-product',
        plan: 'monthly',
      },
    });
    const cartItem = getProductFromCart({
      cart: updatedCart,
      product: { productCode: 'mock-product', plan: 'monthly' },
    });
    expect(cartItem?.quantity).toBe(2);
  });

  test('should remove cart item if updateCart is called with REMOVE_ITEM action', () => {
    const updatedCart = updateCart({
      cart: cartItems,
      type: UPDATE_CART_ACTIONS.REMOVE_ITEM,
      product: {
        productCode: 'mock-product',
        plan: 'monthly',
      },
    });
    const cartItem = getProductFromCart({
      cart: updatedCart,
      product: { productCode: 'mock-product', plan: 'monthly' },
    });
    expect(cartItem).toBeNull();
  });

  test('should toggle plan if togglePlan is called with cartItems and product', () => {
    const updatedCart = updateCart({
      cart: [
        {
          code: 'mock-product',
          quantity: 3,
          plan: 'monthly',
        },
      ],
      type: UPDATE_CART_ACTIONS.TOGGLE_PLAN,
      product: {
        productCode: 'mock-product',
        plan: 'monthly',
      },
    });
    expect(updatedCart[0].plan).toBe('lifetime');
  });

  test('should combine cartItems if same plan occurs twice for single product in cart', () => {
    const updatedCart = updateCart({
      cart: cartItems,
      type: UPDATE_CART_ACTIONS.TOGGLE_PLAN,
      product: {
        productCode: 'mock-product',
        plan: 'monthly',
      },
    });
    expect(updatedCart.length).toBe(1);
    expect(updatedCart[0].plan).toBe('lifetime');
    expect(updatedCart[0].quantity).toBe(6);
  });
});

describe('processPrecheckoutPricing', () => {
  test('should return 0 if no cart items exist', () => {
    const pricingObj = processPrecheckoutPricing({
      cartItems: [],
      productDescriptions: [MOCK_PRODUCT],
    });
    expect(pricingObj).toMatchObject({
      deviceCharges: 0,
      orderedDevices: [],
      gstDevice: 0,
      shipping: 'Free',
      total: 0,
      rentalCharges: 0,
      rentalDevices: [],
      gstRental: 0,
      renewal: 'Every Month',
      invoiceUrl: '',
      refund: null,
    });
  });

  test('should return correct pricing details for both lifetime and rental', () => {
    const mockCart = [
      {
        code: 'mock-product',
        quantity: 3,
        plan: 'monthly' as ProductPlans,
      },
      {
        code: 'mock-product',
        quantity: 2,
        plan: 'lifetime' as ProductPlans,
      },
    ];

    const pricingObj = processPrecheckoutPricing({
      cartItems: mockCart,
      productDescriptions: [{ ...MOCK_PRODUCT, pricing: MOCK_PRICING_WITH_PRICES }],
    });

    expect(pricingObj).toMatchObject(
      expect.objectContaining({
        deviceCharges: 27600,
        gstDevice: 4968,
        shipping: 'Free',
        total: 32568,
        rentalCharges: 1062,
        gstRental: 162,
        refund: null,
      }),
    );
  });

  test('should return only lifetime plan pricing details if only lifetime plan exits ', () => {
    const mockCart = [
      {
        code: 'mock-product',
        quantity: 2,
        plan: 'lifetime' as ProductPlans,
      },
    ];

    const pricingObj = processPrecheckoutPricing({
      cartItems: mockCart,
      productDescriptions: [{ ...MOCK_PRODUCT, pricing: MOCK_PRICING_WITH_PRICES }],
    });

    expect(pricingObj).toMatchObject(
      expect.objectContaining({
        deviceCharges: 24000,
        gstDevice: 4320,
        shipping: 'Free',
        total: 28320,
        rentalCharges: 0,
        gstRental: 0,
        refund: null,
      }),
    );
  });
});

describe('processPrecheckoutPricing', () => {
  test('should return 0 if no cart items exist', () => {
    const pricingObj = processPrecheckoutPricing({
      cartItems: [],
      productDescriptions: [MOCK_PRODUCT],
    });
    expect(pricingObj).toMatchObject({
      deviceCharges: 0,
      orderedDevices: [],
      gstDevice: 0,
      shipping: 'Free',
      total: 0,
      rentalCharges: 0,
      rentalDevices: [],
      gstRental: 0,
      renewal: 'Every Month',
      invoiceUrl: '',
      refund: null,
    });
  });

  test('should return correct pricing if both monthly and life time product exists', () => {
    const mockCart = [
      {
        code: 'mock-product',
        quantity: 3,
        plan: 'monthly' as ProductPlans,
      },
      {
        code: 'mock-product',
        quantity: 2,
        plan: 'lifetime' as ProductPlans,
      },
    ];

    const pricingObj = processPrecheckoutPricing({
      cartItems: mockCart,
      productDescriptions: [{ ...MOCK_PRODUCT, pricing: MOCK_PRICING_WITH_PRICES }],
    });

    expect(pricingObj).toMatchObject(
      expect.objectContaining({
        deviceCharges: 27600,
        gstDevice: 4968,
        shipping: 'Free',
        total: 32568,
        rentalCharges: 1062,
        gstRental: 162,
        refund: null,
      }),
    );
  });

  test('should return only lifetime plan pricing details if only lifetime plan exits ', () => {
    const mockCart = [
      {
        code: 'mock-product',
        quantity: 2,
        plan: 'lifetime' as ProductPlans,
      },
    ];

    const pricingObj = processPrecheckoutPricing({
      cartItems: mockCart,
      productDescriptions: [{ ...MOCK_PRODUCT, pricing: MOCK_PRICING_WITH_PRICES }],
    });

    expect(pricingObj).toMatchObject(
      expect.objectContaining({
        deviceCharges: 24000,
        gstDevice: 4320,
        shipping: 'Free',
        total: 28320,
        rentalCharges: 0,
        gstRental: 0,
        refund: null,
      }),
    );
  });
});

describe('Delivery Address', () => {
  test('should return only operational default delivery address by initializeDeliveryAddresses', () => {
    const response = initializeDeliveryAddresses({
      user: MOCK_USER,
    });
    expect(response[0]).toMatchObject({
      id: '0',
      name: 'Test Name',
      phoneNumber: '1234567890',
      address: 'test operation address',
      city: 'test operation city',
      pincode: '123456',
      state: 'test operation state',
      isSelected: true,
      type: 'default',
    });
  });

  test('should return only registered default delivery address if operational not available by getDefaultDeliveryAddresses', () => {
    const newUserWithoutOperational = {
      ...MOCK_USER,
      business_operation_address: null,
      business_operation_city: null,
      business_operation_state: null,
      business_operation_pin: null,
    };
    const response = initializeDeliveryAddresses({
      user: newUserWithoutOperational,
    });
    expect(response[0]).toMatchObject({
      id: '0',
      name: 'Test Name',
      phoneNumber: '1234567890',
      address: 'test registered address',
      city: 'test registered city',
      pincode: '123456',
      state: 'test registered state',
      isSelected: true,
      type: 'default',
    });
  });

  test('should return true by isValidAddresses if correct delivery addresses exits', () => {
    const isValidAddress = isValidAddresses({ addresses: MOCK_ADDRESSES });
    expect(isValidAddress).toBe(true);
  });

  test('should return false by isValidAddresses if either of delivery address has false value', () => {
    const addresses = [
      ...MOCK_ADDRESSES,
      {
        ...MOCK_ADDRESSES[1],
        phoneNumber: '1234',
      },
    ];

    const isValidAddress = isValidAddresses({ addresses });
    expect(isValidAddress).toBe(false);
  });

  test('should return false by isValidAddresses if either of delivery address has wrong pin code', () => {
    const addresses = [
      ...MOCK_ADDRESSES,
      {
        ...MOCK_ADDRESSES[1],
        pincode: '1234',
      },
    ];

    const isValidAddress = isValidAddresses({ addresses });
    expect(isValidAddress).toBe(false);
  });

  test('should return false by isValidAddresses if no default address type exists', () => {
    const addresses = [...MOCK_ADDRESSES];
    addresses[0].type = 'custom address';
    const isValidAddress = isValidAddresses({ addresses });
    expect(isValidAddress).toBe(false);
  });

  test('should return false by isValidAddresses if no selected address exists', () => {
    const addresses = [...MOCK_ADDRESSES];
    addresses[0].isSelected = false;
    const isValidAddress = isValidAddresses({ addresses });
    expect(isValidAddress).toBe(false);
  });

  test('should return  with new address from updateDeliveryAddress when all the required params are passed for adding new address', () => {
    const newAddress = {
      type: 'custom',
      name: 'Test Name Second',
      phoneNumber: '1234567892',
      pincode: '560034',
      address: 'Test Address Second',
      city: 'Test City',
      state: 'DL',
    };

    const response = updateDeliveryAddress({
      id: '2',
      address: newAddress,
      isNewDeliveryAddress: true,
      addresses: MOCK_ADDRESSES,
      user: MOCK_USER,
    });

    expect(response.length).toBe(3);
    expect(response[2]).toMatchObject({
      id: '2',
      isSelected: true,
      ...newAddress,
    });
  });

  test('should return updated addresses from updateDeliveryAddress when all the required params are passed', () => {
    const response = updateDeliveryAddress({
      id: '0',
      address: MOCK_ADDRESSES[0],
      isNewDeliveryAddress: false,
      addresses: MOCK_ADDRESSES,
      user: MOCK_USER,
    });
    expect(response.length).toBe(2);
    expect(response[0].isSelected).toBe(true);
  });

  test('should call setLocalStorageItem with correct args when saveAddressInLocalStorage is called ', () => {
    const localstorageUtilSpy = jest.spyOn(LocalStorageUtils, 'setItem');
    saveAddressInLocalStorage({
      userId: 'mock-user-id',
      addresses: MOCK_ADDRESSES,
    });
    expect(localstorageUtilSpy).toHaveBeenCalledWith(
      'pos-user-mock-user-id-delivery-address',
      JSON.stringify(MOCK_ADDRESSES),
    );
  });

  test('should return addresses in local storage from getAllDeliveryAddressFromLocalStorage if available AND valid', () => {
    const localstorageUtilSpy = jest.spyOn(LocalStorageUtils, 'getItem');
    localstorageUtilSpy.mockReturnValue(
      JSON.stringify([
        {
          id: '0',
          isSelected: true,
          type: 'default',
          name: 'Test Name First',
          phoneNumber: 1234567892,
          pincode: 560034,
          address: 'Test Address First',
          city: 'Test City',
          state: 'DL',
        },
      ]),
    );
    const response = getAllDeliveryAddressFromLocalStorage({ user: MOCK_USER });
    expect(response.length).toBe(1);
  });

  test('should return default address only  from getAllDeliveryAddressFromLocalStorage if saved address has an invalid item', () => {
    const invalidAddresses = [
      ...MOCK_ADDRESSES,
      {
        ...MOCK_ADDRESSES[1],
        phoneNumber: 123,
      },
    ];
    const localstorageUtilSpy = jest.spyOn(LocalStorageUtils, 'getItem');
    localstorageUtilSpy.mockReturnValue(JSON.stringify(invalidAddresses));
    const response = getAllDeliveryAddressFromLocalStorage({ user: MOCK_USER });
    expect(response.length).toBe(1);
    expect(response[0]).toMatchObject({
      id: '0',
      name: 'Test Name',
      phoneNumber: '1234567890',
      address: 'test operation address',
      city: 'test operation city',
      pincode: '123456',
      state: 'test operation state',
      isSelected: true,
      type: 'default',
    });
  });
});

describe('processPrecheckoutPricing', () => {
  test('should return 0 if no cart items exist', () => {
    const pricingObj = processPrecheckoutPricing({
      cartItems: [],
      productDescriptions: [MOCK_PRODUCT],
    });
    expect(pricingObj).toMatchObject({
      deviceCharges: 0,
      orderedDevices: [],
      gstDevice: 0,
      shipping: 'Free',
      total: 0,
      rentalCharges: 0,
      rentalDevices: [],
      gstRental: 0,
      renewal: 'Every Month',
      invoiceUrl: '',
      refund: null,
    });
  });

  test('should return correct pricing details for both lifetime and rental', () => {
    const mockCart = [
      {
        code: 'mock-product',
        quantity: 3,
        plan: 'monthly' as ProductPlans,
      },
      {
        code: 'mock-product',
        quantity: 2,
        plan: 'lifetime' as ProductPlans,
      },
    ];

    const pricingObj = processPrecheckoutPricing({
      cartItems: mockCart,
      productDescriptions: [{ ...MOCK_PRODUCT, pricing: MOCK_PRICING_WITH_PRICES }],
    });

    expect(pricingObj).toMatchObject(
      expect.objectContaining({
        deviceCharges: 27600,
        gstDevice: 4968,
        shipping: 'Free',
        total: 32568,
        rentalCharges: 1062,
        gstRental: 162,
        refund: null,
      }),
    );
  });

  test('should return only lifetime plan pricing details if only lifetime plan exits ', () => {
    const mockCart = [
      {
        code: 'mock-product',
        quantity: 2,
        plan: 'lifetime' as ProductPlans,
      },
    ];

    const pricingObj = processPrecheckoutPricing({
      cartItems: mockCart,
      productDescriptions: [{ ...MOCK_PRODUCT, pricing: MOCK_PRICING_WITH_PRICES }],
    });

    expect(pricingObj).toMatchObject(
      expect.objectContaining({
        deviceCharges: 24000,
        gstDevice: 4320,
        shipping: 'Free',
        total: 28320,
        rentalCharges: 0,
        gstRental: 0,
        refund: null,
      }),
    );
  });

  describe('POS sidebar condtions', () => {
    test('isPosExperimentEnabled should return true when experiment is disabled for pgos merchant', () => {
      const newAbExperiments = {
        pos_api_merchant_enablement: {
          experimentId: 'mock-exp-id',
          variables: {
            result: 'off',
          },
        },
      };

      const isExperimentEnabled = isPosExperimentEnabled({
        user: MOCK_USER,
        abExperiments: newAbExperiments,
      });
      expect(isExperimentEnabled).toBe(true);
    });

    test('isPosExperimentEnabled should return false when experiment is disabled for non-pgos merchant', () => {
      const newAbExperiments = {
        pos_api_merchant_enablement: {
          experimentId: 'mock-exp-id',
          variables: {
            result: 'off',
          },
        },
      };

      const user = { ...MOCK_USER, is_pgos_merchant: false };

      const isExperimentEnabled = isPosExperimentEnabled({
        user,
        abExperiments: newAbExperiments,
      });
      expect(isExperimentEnabled).toBe(false);
    });

    test('isPosExperimentEnabled should return true when experiment is enabled for non-pgos merchant', () => {
      const newAbExperiments = {
        pos_api_merchant_enablement: {
          experimentId: 'mock-exp-id',
          variables: {
            result: 'on',
          },
        },
      };

      const user = { ...MOCK_USER, is_pgos_merchant: false };

      const isExperimentEnabled = isPosExperimentEnabled({
        user,
        abExperiments: newAbExperiments,
      });
      expect(isExperimentEnabled).toBe(true);
    });

    test('isPosExperimentEnabled should return false for unregistered merchants', () => {
      const newAbExperiments = {
        pos_api_merchant_enablement: {
          experimentId: 'mock-exp-id',
          variables: {
            result: 'off',
          },
        },
      };

      const isExperimentEnabled = isPosExperimentEnabled({
        user: { ...MOCK_USER, business_type: '11' },
        abExperiments: newAbExperiments,
      });
      expect(isExperimentEnabled).toBe(false);
    });
  });
});
describe('getOrderQuantityError', () => {
  test('returns an empty string when errMsg is an empty string', () => {
    const result = getOrderQuantityError({
      currQuantity: 1,
      maxQuantity: 5,
      errMsg: '',
    });
    expect(result).toBe('');
  });

  test('returns errMsg when currQuantity is greater than or equal to maxQuantity', () => {
    const result = getOrderQuantityError({
      currQuantity: 5,
      maxQuantity: 5,
      errMsg: 'Quantity limit reached',
    });
    expect(result).toBe('Quantity limit reached');
  });

  test('returns errMsg when currQuantity is greater than maxQuantity', () => {
    const result = getOrderQuantityError({
      currQuantity: 6,
      maxQuantity: 5,
      errMsg: 'Quantity limit reached',
    });
    expect(result).toBe('Quantity limit reached');
  });

  test('returns an empty string when currQuantity is less than maxQuantity', () => {
    const result = getOrderQuantityError({
      currQuantity: 4,
      maxQuantity: 5,
      errMsg: 'Quantity limit reached',
    });
    expect(result).toBe('');
  });

  test('returns an empty string when maxQuantity is null', () => {
    const result = getOrderQuantityError({
      currQuantity: 4,
      maxQuantity: null,
      errMsg: 'Quantity limit reached',
    });
    expect(result).toBe('');
  });

  test('returns an empty string when currQuantity is 0', () => {
    const result = getOrderQuantityError({
      currQuantity: 0,
      maxQuantity: 5,
      errMsg: 'Quantity limit reached',
    });
    expect(result).toBe('');
  });

  test('returns an empty string when maxQuantity is 0', () => {
    const result = getOrderQuantityError({
      currQuantity: 5,
      maxQuantity: 0,
      errMsg: 'Quantity limit reached',
    });
    expect(result).toBe('');
  });
});

describe('isItemPresentInCart', () => {
  test('returns false if cartItems is empty', () => {
    const isPresent = isItemPresentInCart('a50', []);
    expect(isPresent).toBe(false);
  });

  test('returns true if productCode is present in cart', () => {
    const cartItems: CartItem[] = [
      { code: 'a50', plan: 'monthly', quantity: 1 },
      { code: 'd180', plan: 'monthly', quantity: 1 },
    ];
    const isPresent = isItemPresentInCart('a50', cartItems);
    expect(isPresent).toBe(true);
  });

  test('returns false if productCode is not present in cart', () => {
    const cartItems: CartItem[] = [
      { code: 'a50', plan: 'monthly', quantity: 1 },
      { code: 'd180', plan: 'monthly', quantity: 1 },
    ];
    const isPresent = isItemPresentInCart('wd10', cartItems);
    expect(isPresent).toBe(false);
  });

  test('returns true if productCode is present more than once in cart', () => {
    const cartItems: CartItem[] = [
      { code: 'a50', plan: 'monthly', quantity: 1 },
      { code: 'd180', plan: 'monthly', quantity: 1 },
      { code: 'd180', plan: 'lifetime', quantity: 1 },
    ];
    const isPresent = isItemPresentInCart('d180', cartItems);
    expect(isPresent).toBe(true);
  });
});

describe('getAvailablePricingPlans', () => {
  test('returns both hasMonthlyPlan and hasLifetimePlan as false when productDescription is null', () => {
    const result = getAvailablePricingPlans(null);
    expect(result).toEqual({ hasMonthlyPlan: false, hasLifetimePlan: false });
  });

  test('returns hasLifetimePlan as true when there is a lifetime plan', () => {
    const productDescription: ProductDescription = {
      ...MOCK_PRODUCT,
      pricing: MOCK_PRICING_WITH_ONLY_LIFETIME_PLAN,
    };
    const result = getAvailablePricingPlans(productDescription);
    expect(result).toEqual({ hasMonthlyPlan: false, hasLifetimePlan: true });
  });

  test('returns both hasMonthlyPlan and hasLifetimePlan as true when both plans are present', () => {
    const productDescription: ProductDescription = {
      ...MOCK_PRODUCT,
      pricing: MOCK_PRICING_WITH_PRICES,
    };
    const result = getAvailablePricingPlans(productDescription);
    expect(result).toEqual({ hasMonthlyPlan: true, hasLifetimePlan: true });
  });

  test('returns both hasMonthlyPlan and hasLifetimePlan as false when no valid plans are present', () => {
    const productDescription: ProductDescription = {
      ...MOCK_PRODUCT,
      pricing: [],
    };
    const result = getAvailablePricingPlans(productDescription);
    expect(result).toEqual({ hasMonthlyPlan: false, hasLifetimePlan: false });
  });
});

describe('getInitialPlan', () => {
  test('should return "free" if neither lifetime nor monthly plans are available', () => {
    const mockProductDescription = { ...MOCK_PRODUCT, pricing: [] };
    const result = getInitialPlan({ productDescription: mockProductDescription });
    expect(result).toBe('free');
  });

  test('should return "lifetime" if only lifetime plan is available', () => {
    const mockProductDescription = {
      ...MOCK_PRODUCT,
      pricing: MOCK_PRICING_WITH_ONLY_LIFETIME_PLAN,
    };
    const result = getInitialPlan({ productDescription: mockProductDescription });
    expect(result).toBe('lifetime');
  });

  test('should return "monthly" if monthly plan is available (even if lifetime plan is available)', () => {
    const mockProductDescription = { ...MOCK_PRODUCT, pricing: MOCK_PRICING_WITH_PRICES };
    const result = getInitialPlan({ productDescription: mockProductDescription });
    expect(result).toBe('monthly');
  });
});
