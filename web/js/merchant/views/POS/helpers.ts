import { L2FunnelStageT, PageTypeT } from '@razorpay/universe-utils/analytics';

import { ExperimentInfoType } from 'common/splitz/types';
import { isExperimentEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';
import {
  setItem as setLocalStorageItem,
  getItem as getLocalStorageItem,
} from 'common/utils/localStorage';
import { isProductionEnv } from 'common/utils/rzp-utils';

import {
  ORDER_STATUS_META_DATA,
  CHECKOUT_ERRORS,
  PRODUCT_DESCRIPTIONS,
  UPDATE_CART_ACTIONS,
  deliveryAddressSchema,
  PRODUCT_PLANS,
  FEE_TYPES,
  EASY_DASHBOARD_ROUTES,
} from './constants';
import {
  CartItem,
  ProductDescription,
  ProductDescriptionPricing,
  ProductPlans,
  UpdateCartTypes,
  DeliveryAddress,
  UpdateDeliveryAddress,
  OrderStatusMetaData,
  CreateOrderPayload,
  OrderPricing,
  CheckoutValidationError,
  ProductPricingMap,
  Product,
  OrderDetailsItem,
} from './types';

export const isPosExperimentEnabled = ({
  user,
  abExperiments,
}: {
  user: User;
  abExperiments: ExperimentInfoType;
}): boolean => {
  const isUnregisteredMerchant = user?.business_type === '11' || user.business_type === '2';

  const isWhitelistedForPos = user?.pos_activation_status
    ? user?.pos_activation_flow === 'whitelist'
    : true;

  return (
    isExperimentEnabled(abExperiments?.pos_onboarding) &&
    !!user.is_pgos_merchant &&
    isWhitelistedForPos &&
    !isUnregisteredMerchant
  );
};

const checkIfMerchantHasOnlinePresence = (user): boolean => {
  const hasBusinessWebsite = !!user?.business_website;
  const hasAppstoreUrl = !!user?.appstore_url;
  const hasPlaystoreUrl = !!user?.playstore_url;
  const hasSocialMediaPresence =
    !!user?.merchant_business_detail?.website_details?.social_media_urls?.length;

  return hasBusinessWebsite || hasAppstoreUrl || hasPlaystoreUrl || hasSocialMediaPresence;
};

export const isShopDocUploaded = (user: User): boolean => {
  const { shop_front, shop_interior } = user?.documents ?? {};
  const isShopFrontImageUploaded = !!shop_front?.length;
  const isShopInteriorImageUploaded = !!shop_interior?.length;
  const isShopDocSubmitted = !!user?.pos_activation_status;

  return isShopFrontImageUploaded && isShopInteriorImageUploaded && isShopDocSubmitted;
};

type getCartItemTotal = {
  pricing: ProductDescriptionPricing[];
  quantity: number;
  selectedPlan: ProductPlans;
};

export const getCartItemTotal = ({ pricing, quantity, selectedPlan }: getCartItemTotal): number => {
  const pricingForPlan = pricing.find(({ type }) => type === selectedPlan);
  if (!pricingForPlan) return 0;
  const { breakups } = pricingForPlan;
  const total = breakups.reduce(
    (total, { value, isChargeableAtCheckout }) => total + (isChargeableAtCheckout ? value : 0),
    0,
  );
  return total * quantity;
};

export const saveCartInBrowserStorage = ({
  cartItems,
  userId,
}: {
  cartItems: CartItem[];
  userId: string;
}): void => {
  const key = `pos-user-${userId}-cart`;
  const filteredValidCartItems =
    cartItems?.filter((item) => !!PRODUCT_DESCRIPTIONS?.[item.code]) ?? [];
  setLocalStorageItem(key, JSON.stringify(filteredValidCartItems));
};

export const getCartFromLocalStorage = ({ userId }: { userId: string }): CartItem[] | null => {
  const key = `pos-user-${userId}-cart`;
  const persistedCartItems = getLocalStorageItem(key);
  const cartItems: CartItem[] | null = persistedCartItems ? JSON.parse(persistedCartItems) : null;
  const filteredValidCartItems =
    cartItems?.filter((item) => !!PRODUCT_DESCRIPTIONS?.[item.code]) ?? [];
  return filteredValidCartItems;
};

type GetProductDescriptionWithPricingPlan = {
  productCode: string;
  pricingPlanDict: ProductPricingMap;
};

export const getProductDescriptionWithPricingPlan = ({
  productCode,
  pricingPlanDict,
}: GetProductDescriptionWithPricingPlan): ProductDescription | null => {
  const productDescription = PRODUCT_DESCRIPTIONS[productCode];
  const productPricingWithValues = pricingPlanDict.find((item) => item.code === productCode);
  if (!productPricingWithValues) {
    return null;
  }
  const pricing = productDescription?.pricing;
  if (pricing) {
    const newPricingWithValue = pricing.map((plan) => {
      const { breakups } = plan;
      const newBreakups = breakups.map((breakup) => ({
        ...breakup,
        value: productPricingWithValues?.rate_config?.[breakup.key] ?? 0,
      }));
      return { ...plan, breakups: newBreakups };
    }, []);
    return {
      ...productDescription,
      pricing: newPricingWithValue,
    };
  }
  return productDescription;
};

type ConstructProductDescription = {
  pricingPlanDict: ProductPricingMap;
};

export const constructProductDescription = ({
  pricingPlanDict,
}: ConstructProductDescription): ProductDescription[] => {
  const availableProducts = Object.keys(PRODUCT_DESCRIPTIONS);
  const productDescriptions = availableProducts
    .map((productCode) => getProductDescriptionWithPricingPlan({ productCode, pricingPlanDict }))
    .filter(Boolean);

  return productDescriptions as ProductDescription[];
};

type GetProductFromCart = {
  cart: CartItem[];
  product: Product;
};

export const getProductFromCart = ({ cart, product }: GetProductFromCart): CartItem | null => {
  const { productCode, plan } = product;
  const cartItem = cart.find(
    ({ code, plan: currentPlan }) => code === productCode && currentPlan === plan,
  );
  return cartItem ?? null;
};

type UpdateCartQuantity = {
  cart: CartItem[];
  type: Omit<UpdateCartTypes, 'TOGGLE_PLAN'>;
  product: Product;
};

const updateCartQuantity = ({ cart, type, product }: UpdateCartQuantity): CartItem[] => {
  let newQuantity = 0;
  const cartItem = getProductFromCart({ cart, product });
  const { productCode, plan } = product;
  const currentQuantity = cartItem?.quantity ?? 0;
  const { ADD_TO_CART, INCREASE_QUANTITY, DECREASE_QUANTITY, REMOVE_ITEM } = UPDATE_CART_ACTIONS;

  if ((type === ADD_TO_CART && !cartItem) || !currentQuantity) {
    const newCartItem = {
      quantity: 1,
      code: productCode,
      plan,
    };
    return [...cart, newCartItem];
  }

  if (type === INCREASE_QUANTITY || (type === ADD_TO_CART && cartItem)) {
    newQuantity = currentQuantity + 1;
  }
  if (type === DECREASE_QUANTITY) {
    newQuantity = currentQuantity - 1;
  } else if (type === REMOVE_ITEM) {
    newQuantity = 0;
  }
  const newCartItem = {
    quantity: newQuantity,
    code: productCode,
    plan,
  };

  const newCartItems = cart
    .map((cartItem) =>
      cartItem.code === productCode && cartItem.plan === plan ? newCartItem : cartItem,
    )
    .filter(({ quantity }) => quantity > 0);

  return newCartItems;
};

type ToggleProductPlan = {
  cart: CartItem[];
  product: Product;
};

const toggleProductPlan = ({ cart, product }: ToggleProductPlan): CartItem[] => {
  const { productCode, plan } = product;
  const newPlan = plan === PRODUCT_PLANS.MONTHLY ? PRODUCT_PLANS.LIFETIME : PRODUCT_PLANS.MONTHLY;
  const cartItem = getProductFromCart({ cart, product });
  const similarProduct: Product = {
    productCode,
    plan: newPlan,
  };

  const similarCartItem = getProductFromCart({
    cart,
    product: similarProduct,
  });

  let newCartItem = cartItem;
  const currentQuantity = cartItem?.quantity ?? 0;
  if (similarCartItem) {
    const similarCartItemQuantity = similarCartItem.quantity;
    const newQuantity = currentQuantity + similarCartItemQuantity;
    newCartItem = {
      ...similarCartItem,
      quantity: newQuantity,
    };

    //replace the similar plan
    const newCartItems = cart.map((cartItem) =>
      cartItem.code === newCartItem?.code && cartItem.plan === newCartItem?.plan
        ? newCartItem
        : cartItem,
    );

    //filter out old product && plan
    const filteredCartItems = newCartItems.filter(
      ({ code, plan: currentPlan }) =>
        code !== productCode || (code === productCode && currentPlan !== plan),
    );

    return filteredCartItems;
  } else {
    newCartItem = {
      code: productCode,
      plan: newPlan,
      quantity: currentQuantity,
    };
    const newCartItems = cart.map((cartItem) =>
      cartItem.code === newCartItem?.code && cartItem.plan === plan ? newCartItem : cartItem,
    );
    return newCartItems;
  }
};

type UpdateCart = {
  cart: CartItem[];
  type: UpdateCartTypes;
  product: Product;
};

export const updateCart = ({ cart, type, product }: UpdateCart): CartItem[] => {
  const { ADD_TO_CART, INCREASE_QUANTITY, DECREASE_QUANTITY, REMOVE_ITEM, TOGGLE_PLAN } =
    UPDATE_CART_ACTIONS;

  switch (type) {
    case ADD_TO_CART:
    case INCREASE_QUANTITY:
    case DECREASE_QUANTITY:
    case REMOVE_ITEM:
      return updateCartQuantity({ cart, type, product });
    case TOGGLE_PLAN:
      return toggleProductPlan({ cart, product });
    default:
      return cart;
  }
};

type SaveAddressInLocalStorage = {
  userId: string | undefined;
  addresses: DeliveryAddress[];
};

export const saveAddressInLocalStorage = ({
  userId,
  addresses,
}: SaveAddressInLocalStorage): void => {
  if (userId) {
    const key = `pos-user-${userId}-delivery-address`;
    setLocalStorageItem(key, JSON.stringify(addresses));
  }
};

export const initializeDeliveryAddresses = ({ user }): DeliveryAddress[] => {
  const {
    business_operation_address,
    business_operation_city,
    business_operation_pin,
    business_operation_state,
    business_registered_address,
    business_registered_city,
    business_registered_pin,
    business_registered_state,
    contact_mobile,
    contact_name,
  } = user;

  const defaultAddress: DeliveryAddress = {
    id: '0',
    name: contact_name,
    phoneNumber: contact_mobile,
    address: business_operation_address ?? business_registered_address,
    city: business_operation_city ?? business_registered_city,
    pincode: business_operation_pin ?? business_registered_pin,
    state: business_operation_state ?? business_registered_state,
    isSelected: true,
    type: 'default',
  };

  defaultAddress.isSelected = true;

  saveAddressInLocalStorage({ userId: user.id, addresses: [defaultAddress] });
  return [defaultAddress];
};

type IsValidAddresses = {
  addresses: DeliveryAddress[];
};

export const isValidAddresses = ({ addresses }: IsValidAddresses): boolean => {
  const isSchemaValid = addresses.every((currentAddress) =>
    deliveryAddressSchema.isValidSync(currentAddress),
  );

  const isDefaultAddressExsits = addresses.find(({ type }) => type === 'default');
  const selectedAddresses = addresses.filter(({ isSelected }) => isSelected);

  return !!(isSchemaValid && isDefaultAddressExsits && selectedAddresses.length === 1);
};

interface AddDeliveryAddress extends UpdateDeliveryAddress {
  addresses: DeliveryAddress[];
  user: User;
}

export const updateDeliveryAddress = ({
  id,
  address,
  isNewDeliveryAddress,
  addresses,
  user,
}: AddDeliveryAddress): DeliveryAddress[] => {
  let newAddresses = addresses;

  newAddresses = newAddresses.map((newAddresses) => ({
    ...newAddresses,
    isSelected: false,
  }));

  const newAddress = {
    ...address,
    isSelected: true,
    id: id.toString(),
  };

  if (isNewDeliveryAddress) {
    newAddresses = [...newAddresses, newAddress];
  }
  newAddresses = newAddresses.map((currentAddress, index) =>
    index === Number(id) ? newAddress : currentAddress,
  );

  if (user.id) {
    saveAddressInLocalStorage({ userId: user.id, addresses: newAddresses });
  }

  return newAddresses;
};

export const getAllDeliveryAddressFromLocalStorage = ({ user }): DeliveryAddress[] => {
  const key = `pos-user-${user.id}-delivery-address`;
  let isFetchError = false;
  let deliveryAddresses;

  try {
    const persistedDeliveryAddress = getLocalStorageItem(key);
    deliveryAddresses = persistedDeliveryAddress ? JSON.parse(persistedDeliveryAddress) : null;
  } catch (_error) {
    isFetchError = true;
  }

  if (isFetchError || !deliveryAddresses || !isValidAddresses({ addresses: deliveryAddresses })) {
    deliveryAddresses = initializeDeliveryAddresses({ user });
  }

  return deliveryAddresses;
};

interface OrderStatusData extends OrderStatusMetaData {
  statusTitle: string;
  statusDate: number;
}

export const getOrderStatus = (orderDetails: OrderDetailsItem): OrderStatusData => {
  const { ORDER_DELIVERED, ORDER_RECEIVED, ORDER_REJECTED, REFUND_INITIATED, REFUND_COMPLETED } =
    ORDER_STATUS_META_DATA;

  const { status, delivered_at, rejected_at, arriving_at } = orderDetails;

  if (status === 'delivered')
    return {
      ...ORDER_DELIVERED,
      statusTitle: 'Arrived on',
      statusDate: delivered_at,
    };

  if (status === 'rejected') {
    if (orderDetails?.payment && orderDetails?.refund) {
      if (orderDetails.refund.status === 'processed')
        return {
          ...REFUND_COMPLETED,
          statusTitle: 'Order rejected on',
          statusDate: rejected_at,
        };
      return {
        ...REFUND_INITIATED,
        statusTitle: 'Order rejected on',
        statusDate: rejected_at,
      };
    }
    return {
      ...ORDER_REJECTED,
      statusTitle: 'Order rejected on',
      statusDate: rejected_at,
    };
  }

  return {
    ...ORDER_RECEIVED,
    statusTitle: 'Arriving by',
    statusDate: arriving_at,
  };
};
type GetPayloadForOrderCreate = {
  cartItems: CartItem[];
  deliveryAddresses: DeliveryAddress[];
};
export const getPayloadForOrderCreate = ({
  cartItems,
  deliveryAddresses,
}: GetPayloadForOrderCreate): CreateOrderPayload | null => {
  const selectedDeliveryAddress = deliveryAddresses.find(({ isSelected }) => !!isSelected);

  if (!selectedDeliveryAddress?.pincode || !selectedDeliveryAddress?.phoneNumber) return null;

  const items = cartItems.map((cartItem) => ({
    code: cartItem.code,
    count: cartItem.quantity,
    period: cartItem.plan === PRODUCT_PLANS.MONTHLY ? 'monthly' : 'lifetime',
  }));

  const deliveryAddress = {
    name: selectedDeliveryAddress.name,
    address: selectedDeliveryAddress.address,
    city: selectedDeliveryAddress.city,
    country: 'IN',
    state: selectedDeliveryAddress.state,
    pin_code: selectedDeliveryAddress.pincode,
    phone_no: selectedDeliveryAddress.phoneNumber,
  };

  return {
    delivery_address: deliveryAddress,
    items,
  };
};

export const getCartItemRental = ({
  pricing,
  quantity,
}: Omit<getCartItemTotal, 'selectedPlan'>): number => {
  const pricingForPlan = pricing.find(({ type }) => type === PRODUCT_PLANS.MONTHLY);
  if (!pricingForPlan) return 0;
  const { breakups } = pricingForPlan;
  const total = breakups.reduce(
    (total, { value, key }) => total + (key === FEE_TYPES.MONTHLY ? value : 0),
    0,
  );

  return total * quantity;
};

type ProcessPrecheckoutPricing = {
  cartItems: CartItem[];
  productDescriptions: ProductDescription[];
};

export const processPrecheckoutPricing = ({
  cartItems,
  productDescriptions,
}: ProcessPrecheckoutPricing): OrderPricing => {
  const productDescMap = productDescriptions.reduce(
    (acc, description) => ({ ...acc, [description.code]: description }),
    {},
  );

  const orderedDevices = cartItems.map(({ code, plan, quantity }) => {
    const productDescription: ProductDescription = productDescMap[code];
    const isRental = plan === PRODUCT_PLANS.MONTHLY;
    return {
      productDescription,
      quantity,
      plan,
      deviceTotal: getCartItemTotal({
        pricing: productDescription.pricing,
        selectedPlan: plan,
        quantity,
      }),
      rentalAmount: isRental
        ? getCartItemRental({
            pricing: productDescription.pricing,
            quantity,
          })
        : null,
    };
  });

  const rentalDevices = orderedDevices
    .filter(({ plan }) => plan === PRODUCT_PLANS.MONTHLY)
    .filter(Boolean);

  const baseAmount = orderedDevices.reduce((acc, orderItem) => (acc += orderItem.deviceTotal), 0);
  const gstBaseAmount = Math.floor((18 / 100) * baseAmount);
  const totalRentalAmount = rentalDevices.reduce(
    (acc, orderItem) => (acc += orderItem?.rentalAmount ?? 0),
    0,
  );
  const gstRentalAmount = Math.floor((18 / 100) * totalRentalAmount);

  const pricingObj: OrderPricing = {
    deviceCharges: baseAmount,
    orderedDevices,
    gstDevice: gstBaseAmount,
    shipping: 'Free',
    total: baseAmount + gstBaseAmount,
    rentalCharges: totalRentalAmount + gstRentalAmount,
    rentalDevices,
    gstRental: gstRentalAmount,
    renewal: 'Every Month',
    invoiceUrl: '',
    refund: null,
  };

  return pricingObj;
};

type ValidatePrecheckoutProps = {
  cartItems: CartItem[];
  user: User | null;
  latestOrder: OrderDetailsItem | undefined;
};

export const validatePrecheckout = ({
  user,
  cartItems,
  latestOrder,
}: ValidatePrecheckoutProps): CheckoutValidationError | null => {
  const totalQuantity = cartItems.reduce((acc, cartItems) => (acc += cartItems.quantity), 0);

  if (!user) return CHECKOUT_ERRORS.ORDER_CREATE_FAILED;

  if (totalQuantity < 1) {
    return CHECKOUT_ERRORS.NO_CART_ITEMS;
  }

  if (latestOrder) {
    const { status } = latestOrder;
    if (status === 'paid' || status === 'rejected' || status === 'delivered')
      return CHECKOUT_ERRORS.MULTIPLE_ORDERS;
  }

  if (totalQuantity > 9) {
    return CHECKOUT_ERRORS.MAX_CART_ITEMS;
  }

  if (user?.pos_activation_status === 'rejected') {
    return CHECKOUT_ERRORS.KYC_REJECTED;
  }

  const isPOSPaymentChannelSelected =
    user?.merchant_business_detail?.website_details?.physical_store;

  const hasShopImages = isShopDocUploaded(user);

  if (!isPOSPaymentChannelSelected && checkIfMerchantHasOnlinePresence(user) && !hasShopImages) {
    return CHECKOUT_ERRORS.ORDER_CREATE_FAILED;
  }

  return null;
};

type Prices = {
  monthly: number;
  setupFee: number;
  lifetime: number;
};

export const getPricingByProduct = ({
  productDescription,
}: {
  productDescription: ProductDescription;
}): Prices => {
  const subscriptionPricingBreakups =
    productDescription.pricing.find(({ type }) => type === PRODUCT_PLANS.MONTHLY)?.breakups || [];

  const subscriptionMonthlyAmount =
    subscriptionPricingBreakups.find(({ key }) => key === FEE_TYPES.MONTHLY)?.value ?? 0;

  const subscriptionSetupAmount =
    subscriptionPricingBreakups.find(({ key }) => key === FEE_TYPES.SETUP_FEE)?.value ?? 0;

  const subscriptionLifetimeAmount =
    productDescription.pricing.find(({ type }) => type === PRODUCT_PLANS.LIFETIME)?.breakups[0]
      .value ?? 0;

  return {
    monthly: subscriptionMonthlyAmount,
    setupFee: subscriptionSetupAmount,
    lifetime: subscriptionLifetimeAmount,
  };
};

interface CommsAnalyticsResponse {
  l2FunnelStage: L2FunnelStageT;
  subSection: string;
  pageType: PageTypeT;
}

export const getCommsAnalytics = ({
  pathname,
  productName = '',
}: {
  pathname: string;
  productName?: string;
}): CommsAnalyticsResponse => {
  if (productName) {
    return {
      pageType: 'Merchant Activation Status Bar - POS Product Description',
      l2FunnelStage: 'Merchant Activation Status Bar - POS Product Description',
      subSection: 'POS Product Description',
    };
  }
  if (pathname.startsWith('/pos/catalog')) {
    return {
      pageType: 'Merchant Activation Status Bar - POS Catalog',
      l2FunnelStage: 'Merchant Activation Status Bar - POS Catalog',
      subSection: 'POS Catalog',
    };
  }
  if (pathname.startsWith('/pos/orders')) {
    return {
      pageType: 'Merchant Activation Status Bar - Pre-checkout',
      l2FunnelStage: 'Merchant Activation Status Bar - Pre-checkout',
      subSection: 'Pre-checkout',
    };
  }
  return {
    pageType: 'Merchant Activation Status Bar - POS Catalog',
    l2FunnelStage: 'Merchant Activation Status Bar - POS Catalog',
    subSection: 'POS Catalog',
  };
};

type GetProductFromProductDescriptions = {
  code: string;
  productDescriptions: ProductDescription[];
};

export const getProductFromProductDescriptions = ({
  code,
  productDescriptions,
}: GetProductFromProductDescriptions): ProductDescription | null => {
  return productDescriptions.find(({ code: productCode }) => productCode === code) ?? null;
};

type GetOrderPricingFromOrderDetails = {
  orderRespData: OrderDetailsItem;
  productDescriptions: ProductDescription[];
};

export const getOrderPricingFromOrderDetails = ({
  orderRespData,
  productDescriptions,
}: GetOrderPricingFromOrderDetails): OrderPricing => {
  const { amount, items, rental_amount, status, refund } = orderRespData;
  const productDescMap = productDescriptions.reduce(
    (acc, description) => ({ ...acc, [description.code]: description }),
    {},
  );

  const orderedDevices = items.map(({ code, count, period }) => {
    const productDescription = productDescMap[code];
    const isRental = period === PRODUCT_PLANS.MONTHLY;
    return {
      productDescription,
      quantity: count,
      plan: period as ProductPlans,
      deviceTotal: getCartItemTotal({
        pricing: productDescription.pricing,
        selectedPlan: period as ProductPlans,
        quantity: count,
      }),
      rentalAmount: isRental
        ? getCartItemRental({
            pricing: productDescription.pricing,
            quantity: count,
          })
        : null,
    };
  });

  const rentalDevices = orderedDevices
    .filter(({ plan }) => plan === PRODUCT_PLANS.MONTHLY)
    .filter(Boolean);

  const pricingObj: OrderPricing = {
    deviceCharges: amount.base,
    orderedDevices,
    gstDevice: amount.gst,
    shipping: 'Free',
    total: amount.total,
    rentalCharges: rental_amount?.base ?? 0,
    rentalDevices,
    gstRental: rental_amount?.gst ?? 0,
    renewal: 'Every Month',
    invoiceUrl: '',
    refund: null,
  };

  const isAmountRefunded = status === 'rejected' && !!refund && refund?.status === 'processed';
  if (isAmountRefunded) {
    pricingObj.refund = {
      amount: refund.amount,
      id: refund.id,
      refId: Object.values(refund?.acquirer_data ?? {})[0],
      status: 'processed',
    };
  }

  return pricingObj;
};

export const loadCheckoutForPos = (): Promise<unknown> => {
  // eslint-disable-next-line consistent-return
  return new Promise((resolve, reject) => {
    if (window.Razorpay) return resolve(null);

    const script = document.createElement('script');
    script.src = isProductionEnv()
      ? 'https://checkout.razorpay.com/v1/checkout.js'
      : 'https://betacdn.np.razorpay.in/checkout/builds/branch-builds/feat/pos-device-store/v1/checkout.js';
    script.onload = resolve;
    script.onerror = reject;
    document.head.appendChild(script);
  });
};

type PreCheckoutAdditionalDetails = {
  isRequired: boolean;
  url: string | null;
};

export const preCheckoutAdditionalDetails = ({
  user,
}: {
  user: User;
}): PreCheckoutAdditionalDetails => {
  const isPOSPaymentChannelSelected =
    user?.merchant_business_detail?.website_details?.physical_store;

  const hasShopImages = isShopDocUploaded(user);

  if (!user?.submitted) {
    return {
      isRequired: true,
      url: isPOSPaymentChannelSelected
        ? `${window.EASY_ONBOARDING_URL}${EASY_DASHBOARD_ROUTES.l2onboarding}`
        : `${window.EASY_ONBOARDING_URL}${EASY_DASHBOARD_ROUTES.l2onboardingWithIntent}`,
    };
  } else if (!checkIfMerchantHasOnlinePresence(user) && !hasShopImages) {
    return {
      isRequired: true,
      url: isPOSPaymentChannelSelected
        ? `${window.EASY_ONBOARDING_URL}${EASY_DASHBOARD_ROUTES.storeDetails}`
        : `${window.EASY_ONBOARDING_URL}${EASY_DASHBOARD_ROUTES.storeDetailsWithIntent}`,
    };
  }

  return {
    isRequired: false,
    url: null,
  };
};
