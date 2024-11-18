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
  CONSENT_STEP,
  CONSENT_COMPONENT,
  AGREEMENT_STATUS_FIELD,
  PRICING_CONSENT_FIELD,
  AGREEMENT_STEP,
  AGREEMENT_COMPONENT,
  DEVICE_CATALOG_COMPONENT,
  DEVICE_SELECTION_STEP,
  SOUNDBOX,
  STANDEEANDSTICKER,
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
  OfferConfig,
  ModularOnboardingStep,
  WorkflowConfig,
  ModularOnboardingStepComponent,
  RateConfig,
  PricingBreakupkeys,
  PricingTypes,
  DeviceMetaData,
} from './types';

export const isValidFee = (value: number | null | undefined): boolean => {
  return value === 0 || (value !== undefined && value !== null);
};

export const isPosExperimentEnabled = ({
  user,
  abExperiments,
}: {
  user: User;
  abExperiments: ExperimentInfoType;
}): boolean => {
  const isUnregisteredMerchant = user?.business_type === '11' || user.business_type === '2';
  const allowedFlows = ['whitelist', 'greylist'];

  const isWhitelistedForPos =
    user?.pos_activation_status !== null && typeof user?.pos_activation_status !== 'undefined'
      ? allowedFlows.includes(user?.pos_activation_flow ?? '')
      : true;

  const isBlockedForSignUpCampaign =
    user.user?.signup_campaign === 'assisted_onboarding' ||
    user.user?.signup_campaign === 'phantom_onboarding';

  const checks = isWhitelistedForPos && !isUnregisteredMerchant && !isBlockedForSignUpCampaign;

  if (user.is_pgos_merchant) {
    return checks;
  } else {
    // For non-pgos(API) merchants, we need to check the experiment & the checks
    return isExperimentEnabled(abExperiments?.pos_api_merchant_enablement) && checks;
  }
};

export const isPosSoundboxEnabled = ({ abExperiments }: { abExperiments: ExperimentInfoType }) => {
  return abExperiments?.pos_soundbox?.variables?.result === 'on';
};

export const getProductOffers = ({
  abExperiments,
}: {
  abExperiments: ExperimentInfoType;
}): Record<string, OfferConfig> | null => {
  const offerConfig = (abExperiments?.pos_onboarding?.variables?.offerConfig as string) ?? '';

  if (!offerConfig) return null;

  return JSON.parse(offerConfig) as Record<string, OfferConfig>;
};

type FetchProductOffers = {
  isEnabled: boolean;
  offers: Record<string, OfferConfig> | null;
};

export const fetchProductOffers = ({
  abExperiments,
}: {
  abExperiments: ExperimentInfoType;
}): FetchProductOffers => {
  const isEnabled = abExperiments?.pos_onboarding?.variables?.offersEnabled === 'on';
  return {
    isEnabled,
    offers: isEnabled ? getProductOffers({ abExperiments }) : null,
  };
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
  isConsiderPrevValue?: boolean;
};

type getCartItemTotalReturnType = {
  value: number;
  prevValue: number;
};

export const getCartItemTotal = ({
  pricing,
  quantity,
  selectedPlan,
}: getCartItemTotal): getCartItemTotalReturnType => {
  const pricingForPlan = pricing.find(({ type }) => type === selectedPlan);
  if (!pricingForPlan) return { value: 0, prevValue: 0 };

  const { breakups } = pricingForPlan;

  const total = breakups.reduce((total, { value, isChargeableAtCheckout }) => {
    return total + (isChargeableAtCheckout ? value : 0);
  }, 0);

  const prevTotal = breakups.reduce((total, { value, prevValue, isChargeableAtCheckout }) => {
    const consideredValue = isValidFee(prevValue) ? (prevValue as number) : value;
    return total + (isChargeableAtCheckout ? consideredValue : 0);
  }, 0);

  return {
    value: total * quantity,
    prevValue: prevTotal * quantity,
  };
};

export const getOrderQuantityError = ({
  currQuantity,
  maxQuantity,
  errMsg,
}: {
  currQuantity: number;
  maxQuantity: number | null;
  errMsg: string;
}): string => {
  if (!errMsg) return '';
  if (maxQuantity && currQuantity && currQuantity >= maxQuantity) {
    return errMsg;
  }
  return '';
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

type GetPricingValuesReturnType = {
  value: number;
  prevValue: number | null;
  nextValue: number;
};
type GetPricingValues = {
  rateConfig: RateConfig;
  offerConfig?: OfferConfig | null;
  planType: PricingTypes;
  breakupKey: PricingBreakupkeys;
};
const getPricingValues = ({
  rateConfig,
  offerConfig,
  planType,
  breakupKey,
}: GetPricingValues): GetPricingValuesReturnType => {
  const plan = rateConfig?.plans.find((plan) => plan.plan_name === planType);
  const getNextValue = () => {
    if (planType === 'lifetime') return plan?.one_time_charge ?? 0;
    if (planType === 'monthly') {
      if (breakupKey === 'monthly') return plan?.rental_charges ?? 0;
      if (breakupKey === 'setup_fee') return plan?.setup_fee ?? 0;
      if (breakupKey === 'lifetime') return plan?.one_time_charge ?? 0;
    }
    return 0;
  };
  return {
    value: getNextValue(),
    prevValue: offerConfig ? offerConfig?.preRateConfig[breakupKey] : null,
    nextValue: getNextValue(),
  };
};

type GetFilteredPricing = {
  deviceConfig: {
    name: string;
    code: string;
    entity_type?: string;
    rate_config: {
      monthly: number;
      lifetime: number;
      setup_fee: number;
    };
    metadata?: DeviceMetaData;
  };
  pricing: ProductDescriptionPricing[];
};
const getFilteredPricing = ({
  pricing,
  deviceConfig,
}: GetFilteredPricing): ProductDescriptionPricing[] => {
  const availablePlans = deviceConfig.metadata?.rate_config_v2?.plans;
  const result = pricing.filter((pricingPlan) => {
    return availablePlans?.find((plan) => pricingPlan.type === plan.plan_name);
  });
  return result;
};

type GetProductDescriptionWithPricingPlan = {
  productCode: string;
  pricingPlanDict: ProductPricingMap;
  offerConfigForProduct?: OfferConfig | null;
};

export const getProductDescriptionWithPricingPlan = ({
  productCode,
  pricingPlanDict,
  offerConfigForProduct,
}: GetProductDescriptionWithPricingPlan): ProductDescription | null => {
  const productDescription = PRODUCT_DESCRIPTIONS[productCode];
  const productPricingWithValues = pricingPlanDict.find((item) => item.code === productCode);
  if (!productPricingWithValues) {
    return null;
  }
  const pricing = productDescription?.pricing;
  const filteredPricingPlans = getFilteredPricing({
    pricing,
    deviceConfig: productPricingWithValues,
  });
  const shouldReadFromRateConfigV2 =
    !!productPricingWithValues?.metadata?.rate_config_v2?.plans?.length;
  if (pricing) {
    const newRentalDiscountPeriod = shouldReadFromRateConfigV2
      ? productPricingWithValues?.metadata?.rate_config_v2?.rental_discount_periods
      : productDescription.rentalDiscountPeriod;
    const newPricingWithValue = (shouldReadFromRateConfigV2 ? filteredPricingPlans : pricing).map(
      (plan) => {
        const { breakups } = plan;
        const newBreakups = breakups.map((breakup) => {
          if (productPricingWithValues?.metadata?.rate_config_v2?.plans?.length) {
            return {
              ...breakup,
              ...getPricingValues({
                rateConfig: productPricingWithValues.metadata.rate_config_v2,
                offerConfig: offerConfigForProduct,
                planType: plan.type,
                breakupKey: breakup.key,
              }),
            };
          }
          return {
            ...breakup,
            value: productPricingWithValues?.rate_config?.[breakup.key] ?? 0,
            prevValue: offerConfigForProduct
              ? offerConfigForProduct?.preRateConfig[breakup.key]
              : null,
            nextValue: offerConfigForProduct
              ? offerConfigForProduct?.nextRateConfig[breakup.key]
              : null,
          };
        });
        return {
          ...plan,
          breakups: newBreakups,
        };
      },
      [],
    );
    return {
      ...productDescription,
      pricing: newPricingWithValue,
      rentalDiscountPeriod: newRentalDiscountPeriod ?? 0,
      offer: offerConfigForProduct
        ? {
            offerText: offerConfigForProduct.offerText ?? null,
            pdpOfferText: offerConfigForProduct.pdpOfferText ?? null,
            partnerOfferText: offerConfigForProduct.partnerOfferText ?? null,
            partnerPdpOfferText: offerConfigForProduct.partnerPdpOfferText ?? null,
          }
        : null,
      isPartnerPricing: productPricingWithValues.entity_type === 'partner',
    };
  }
  return productDescription;
};

type ConstructProductDescription = {
  pricingPlanDict: ProductPricingMap;
  offerConfig?: Record<string, OfferConfig> | null;
  isSoundboxEnabled?: boolean;
};

export const constructProductDescription = ({
  pricingPlanDict,
  offerConfig,
  isSoundboxEnabled,
}: ConstructProductDescription): ProductDescription[] => {
  let availableProducts = Object.keys(PRODUCT_DESCRIPTIONS);
  if (!isSoundboxEnabled) {
    availableProducts = availableProducts.filter(
      (productCode) => productCode !== SOUNDBOX.code && productCode !== STANDEEANDSTICKER.code,
    );
  }
  const productDescriptions = availableProducts
    .map((productCode) =>
      getProductDescriptionWithPricingPlan({
        productCode,
        pricingPlanDict,
        offerConfigForProduct: offerConfig?.[productCode] ?? null,
      }),
    )
    .filter(Boolean);
  return productDescriptions as ProductDescription[];
};

export const isItemPresentInCart = (productCode: string, cartItems: CartItem[]): boolean => {
  if (!cartItems?.length) return false;
  return !!cartItems.find((item) => item.code === productCode);
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
  maxOrder?: number | null | undefined;
};

const updateCartQuantity = ({ cart, type, product, maxOrder }: UpdateCartQuantity): CartItem[] => {
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
    if (maxOrder && currentQuantity && currentQuantity >= maxOrder) {
      newQuantity = maxOrder;
    } else {
      newQuantity = currentQuantity + 1;
    }
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
  maxOrder?: number | null;
};

export const updateCart = ({ cart, type, product, maxOrder }: UpdateCart): CartItem[] => {
  const { ADD_TO_CART, INCREASE_QUANTITY, DECREASE_QUANTITY, REMOVE_ITEM, TOGGLE_PLAN } =
    UPDATE_CART_ACTIONS;
  switch (type) {
    case ADD_TO_CART:
    case INCREASE_QUANTITY:
    case DECREASE_QUANTITY:
    case REMOVE_ITEM:
      return updateCartQuantity({ cart, type, product, maxOrder });
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
}: Omit<getCartItemTotal, 'selectedPlan'>): {
  value: number;
  prevValue: number;
  nextValue: number | null;
} => {
  const pricingForPlan = pricing.find(({ type }) => type === PRODUCT_PLANS.MONTHLY);
  if (!pricingForPlan) return { value: 0, prevValue: 0, nextValue: null };

  const { breakups } = pricingForPlan;
  const total = breakups.reduce(
    (total, { value, key }) => total + (key === FEE_TYPES.MONTHLY ? value : 0),
    0,
  );

  const prevTotal = breakups.reduce((total, { value, prevValue, key }) => {
    const consideredValue = prevValue && isValidFee(prevValue) ? prevValue : value;
    return total + (key === FEE_TYPES.MONTHLY ? consideredValue : 0);
  }, 0);

  const hasValidNextValue = breakups.some(({ nextValue }) => isValidFee(nextValue));

  const nextTotal = hasValidNextValue
    ? breakups.reduce((total, { nextValue, key }) => {
        return total + (key === FEE_TYPES.MONTHLY ? Number(nextValue) : 0);
      }, 0)
    : null;

  return {
    value: total * quantity,
    prevValue: prevTotal * quantity,
    nextValue: nextTotal ? nextTotal * quantity : null,
  };
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

  const isPartnerPricing = productDescriptions.some(
    (productDescription) => productDescription.isPartnerPricing === true,
  );
  const orderedDevices = cartItems.map(({ code, plan, quantity }) => {
    const productDescription: ProductDescription = productDescMap[code];
    const isRental = plan === PRODUCT_PLANS.MONTHLY;

    const total = getCartItemTotal({
      pricing: productDescription.pricing,
      selectedPlan: plan,
      quantity,
    });

    const rentalTotal = isRental
      ? getCartItemRental({
          pricing: productDescription.pricing,
          quantity,
        })
      : null;

    return {
      productDescription,
      quantity,
      plan,
      deviceTotal: total.value,
      prevDeviceTotal: total.prevValue,
      rentalAmount: rentalTotal?.value ?? null,
      prevRetalAmount: rentalTotal?.prevValue ?? null,
      nextRentalAmount: rentalTotal?.nextValue ?? null,
    };
  });

  const orderedDevicesWithOffer = orderedDevices.filter(
    ({ productDescription }) => !!productDescription.offer,
  );

  const orderedDevicesWithoutOffer = orderedDevices.filter(
    ({ productDescription }) => !productDescription.offer,
  );

  const rentalDevices = orderedDevices
    .filter(({ plan }) => plan === PRODUCT_PLANS.MONTHLY)
    .filter(Boolean);

  const rentalDevicesWithOffer = rentalDevices.filter(
    ({ productDescription }) => !!productDescription.offer,
  );

  const rentalDevicesWithoutOffer = rentalDevices.filter(
    ({ productDescription }) => !productDescription.offer,
  );

  const baseAmount = orderedDevices.reduce((acc, orderItem) => (acc += orderItem.deviceTotal), 0);
  const gstBaseAmount = Math.floor((18 / 100) * baseAmount);
  const totalRentalAmount = rentalDevices.reduce(
    (acc, orderItem) => (acc += orderItem?.rentalAmount ?? 0),
    0,
  );
  const gstRentalAmount = Math.floor((18 / 100) * totalRentalAmount);

  const pricingObj: OrderPricing = {
    deviceCharges: baseAmount,
    orderedDevices: orderedDevicesWithOffer.length ? orderedDevicesWithoutOffer : orderedDevices,
    gstDevice: gstBaseAmount,
    shipping: 'Free',
    total: baseAmount + gstBaseAmount,
    rentalCharges: totalRentalAmount + gstRentalAmount,
    rentalDevices: rentalDevicesWithOffer.length ? rentalDevicesWithoutOffer : rentalDevices,
    gstRental: gstRentalAmount,
    renewal: 'Every Month',
    invoiceUrl: '',
    refund: null,
    orderedDevicesWithOffer,
    rentalDevicesWithOffer,
    isPartnerPricing,
  };

  return pricingObj;
};

type ValidatePrecheckoutProps = {
  cartItems: CartItem[];
  user: User | null;
  latestOrder: OrderDetailsItem | undefined;
  isDeliveryAddressFormOpen: boolean;
};

export const validatePrecheckout = ({
  user,
  cartItems,
  latestOrder,
  isDeliveryAddressFormOpen,
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

  if (isDeliveryAddressFormOpen) {
    return CHECKOUT_ERRORS.DELIVERY_ADDRESS_FORM_OPEN;
  }

  return null;
};

type Prices = {
  monthly: number;
  setupFee: number;
  lifetime: number;
  offer: {
    prevMonthly: number;
    prevSetupFee: number;
    prevLifetime: number;
    nextMonthly: number;
  } | null;
};

export const getPricingByProduct = ({
  productDescription,
}: {
  productDescription: ProductDescription;
}): Prices => {
  const subscriptionPricingBreakups =
    productDescription.pricing.find(({ type }) => type === PRODUCT_PLANS.MONTHLY)?.breakups || [];

  const subscriptionMonthlyAmount = subscriptionPricingBreakups.find(
    ({ key }) => key === FEE_TYPES.MONTHLY,
  );

  const subscriptionSetupAmount = subscriptionPricingBreakups.find(
    ({ key }) => key === FEE_TYPES.SETUP_FEE,
  );

  const subscriptionLifetimeAmount = productDescription.pricing.find(
    ({ type }) => type === PRODUCT_PLANS.LIFETIME,
  )?.breakups[0];

  return {
    monthly: subscriptionMonthlyAmount?.value ?? 0,
    setupFee: subscriptionSetupAmount?.value ?? 0,
    lifetime: subscriptionLifetimeAmount?.value ?? 0,
    offer: productDescription.offer
      ? {
          prevMonthly: subscriptionMonthlyAmount?.prevValue ?? 0,
          prevSetupFee: subscriptionSetupAmount?.prevValue ?? 0,
          prevLifetime: subscriptionLifetimeAmount?.prevValue ?? 0,
          nextMonthly: subscriptionMonthlyAmount?.nextValue ?? 0,
        }
      : null,
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

type GetAvailablePricingPlansReturnType = {
  hasMonthlyPlan: boolean;
  hasLifetimePlan: boolean;
};
export const getAvailablePricingPlans = (
  productDescription: ProductDescription | null,
): GetAvailablePricingPlansReturnType => {
  if (!productDescription) return { hasMonthlyPlan: false, hasLifetimePlan: false };
  let hasMonthlyPlan = false;
  let hasLifetimePlan = false;
  productDescription.pricing.forEach((plan) => {
    if (plan.type === 'monthly') {
      hasMonthlyPlan = true;
    } else if (plan.type === 'lifetime') {
      hasLifetimePlan = true;
    }
  });
  return { hasMonthlyPlan, hasLifetimePlan };
};

type GetInitialPlan = {
  productDescription: ProductDescription | null;
};

type GetInitialPlanReturnType = PricingTypes;
export const getInitialPlan = ({
  productDescription,
}: GetInitialPlan): GetInitialPlanReturnType => {
  const { hasLifetimePlan, hasMonthlyPlan } = getAvailablePricingPlans(productDescription);
  if (!hasLifetimePlan && !hasMonthlyPlan) return 'free'; //device is for free
  if (hasLifetimePlan && !hasMonthlyPlan) return 'lifetime';
  return 'monthly';
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
  const isPartnerPricing = productDescriptions.some(
    (productDescription) => productDescription.isPartnerPricing === true,
  );
  const orderedDevices = items.map(({ code, count, period }) => {
    const productDescription = productDescMap[code];
    const isRental = period === PRODUCT_PLANS.MONTHLY;
    const deviceTotal = getCartItemTotal({
      pricing: productDescription.pricing,
      selectedPlan: period as ProductPlans,
      quantity: count,
    });

    const rentalTotal = getCartItemRental({
      pricing: productDescription.pricing,
      quantity: count,
    });

    return {
      productDescription,
      quantity: count,
      plan: period as ProductPlans,
      deviceTotal: deviceTotal.value,
      prevDeviceTotal: deviceTotal.prevValue,
      rentalAmount: isRental ? rentalTotal.value : null,
      prevRetalAmount: rentalTotal?.prevValue ?? null,
      nextRentalAmount: rentalTotal?.nextValue ?? null,
    };
  });

  const orderedDevicesWithOffer = orderedDevices.filter(
    ({ productDescription }) => !!productDescription.offer,
  );

  const orderedDevicesWithoutOffer = orderedDevices.filter(
    ({ productDescription }) => !productDescription.offer,
  );

  const rentalDevices = orderedDevices
    .filter(({ plan }) => plan === PRODUCT_PLANS.MONTHLY)
    .filter(Boolean);

  const rentalDevicesWithOffer = rentalDevices.filter(
    ({ productDescription }) => !!productDescription.offer,
  );

  const rentalDevicesWithoutOffer = rentalDevices.filter(
    ({ productDescription }) => !productDescription.offer,
  );

  const pricingObj: OrderPricing = {
    deviceCharges: amount.base,
    orderedDevices: orderedDevicesWithOffer.length ? orderedDevicesWithoutOffer : orderedDevices,
    gstDevice: amount.gst,
    shipping: 'Free',
    total: amount.total,
    rentalCharges: rental_amount?.base ?? 0,
    rentalDevices: rentalDevicesWithOffer.length ? rentalDevicesWithoutOffer : rentalDevices,
    gstRental: rental_amount?.gst ?? 0,
    renewal: 'Every Month',
    invoiceUrl: '',
    orderedDevicesWithOffer,
    refund: null,
    rentalDevicesWithOffer,
    isPartnerPricing,
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
  isCaseCreateRequired: boolean;
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
      isCaseCreateRequired: false,
    };
  } else if (
    (!checkIfMerchantHasOnlinePresence(user) && !hasShopImages) ||
    (user.pos_activation_flow === 'greylist' && !hasShopImages)
  ) {
    return {
      isRequired: true,
      url: isPOSPaymentChannelSelected
        ? `${window.EASY_ONBOARDING_URL}${EASY_DASHBOARD_ROUTES.storeDetails}`
        : `${window.EASY_ONBOARDING_URL}${EASY_DASHBOARD_ROUTES.storeDetailsWithIntent}`,
      isCaseCreateRequired: false,
    };
  } else if (
    (checkIfMerchantHasOnlinePresence(user) && !isPOSPaymentChannelSelected && !hasShopImages) ||
    (checkIfMerchantHasOnlinePresence(user) &&
      !!user?.activation_status &&
      !user?.pos_activation_status)
  ) {
    return {
      isRequired: false,
      url: null,
      isCaseCreateRequired: true,
    };
  }

  return {
    isRequired: false,
    url: null,
    isCaseCreateRequired: false,
  };
};

export const checkIfPanIndiaLive = ({
  abExperiments,
}: {
  abExperiments: ExperimentInfoType;
}): boolean => abExperiments?.pos_onboarding?.variables?.isPanIndiaLive === 'on';

export const getStepDataByStepName = (
  workflowConfig: WorkflowConfig | null | undefined,
  stepName: string,
) => {
  if (!workflowConfig?.workflow_data?.milestones[0]) return null;
  const data = workflowConfig?.workflow_data.milestones[0]?.steps.find(
    (step: ModularOnboardingStep) => step.name === stepName,
  );
  return data;
};

export const getComponentByName = (
  components: ModularOnboardingStepComponent[] = [],
  compName: string,
) => {
  if (!components || !components.length || !compName) return null;
  return components.find((component) => component.name === compName);
};

export const getAllFieldValuesOfAComponent = (component) => {
  if (!component || !component.fields) return {};
  const data = component.fields.reduce((acc, field) => {
    if (!acc[field.name]) {
      acc[field.name] = field.value;
    }
    return acc;
  }, {});
  return data;
};

export const isCustomRateEnabled = (workflowConfig) => {
  const consentStep = getStepDataByStepName(workflowConfig, CONSENT_STEP);
  if (!consentStep) return false;
  const consentComponent = getComponentByName(consentStep.components, CONSENT_COMPONENT);
  if (!consentComponent) return false;
  const consentFields = consentComponent.fields;
  const pricingConsentField: any = consentFields.find(
    (field: any) => field.name === PRICING_CONSENT_FIELD,
  );
  return pricingConsentField?.is_required;
};

export const getAgreementStatus = (workflowConfig) => {
  const consentStep = getStepDataByStepName(workflowConfig, AGREEMENT_STEP);
  if (!consentStep) return { is_required: false, status: '' };
  const consentComponent = getComponentByName(consentStep.components, AGREEMENT_COMPONENT);
  if (!consentComponent) return { is_required: false, status: '' };
  const consentFields = consentComponent.fields;
  const agreementField: any = consentFields.find(
    (field: any) => field.name === AGREEMENT_STATUS_FIELD,
  );
  return { is_required: agreementField?.is_required, status: agreementField?.value };
};

export const getDeviceChargesData = (workflowConfig) => {
  const tableStructure: Array<[string, (item: any) => string]> = [
    ['Device Type', (item) => item.device_model?.toUpperCase() ?? ''],
    ['Subscription Plan', (item) => item.renewal ?? ''],
    ['Rental Fee Per Device Unit', (item) => item.rental_charge ?? ''],
    ['Total Device Quantity', (item) => item.quantity ?? ''],
    ['Advance Rental Period', (item) => item.advanced_rental_periods ?? ''],
    [
      'Advance  Rental Fee Payable at Setup (excluding GST)',
      (item) => item.total_advance_rental_charge ?? '',
    ],
    ['Device Charges (excl GST)', (item) => item.total_setup_charge ?? ''],
    ['Paper Roll Charges (excl GST)', (item) => item.total_paper_roll_charge ?? ''],
    ['Total GST on Fee Payable at Setup', (item) => item.total_order_gst_amount?.toFixed(2) ?? ''],
  ];

  const deviceSelectionStep = getStepDataByStepName(workflowConfig?.data, DEVICE_SELECTION_STEP);

  if (!deviceSelectionStep) return { tableStructure, data: [], overallSetupFee: 0 };
  const deviceCatalogComponent = getComponentByName(
    deviceSelectionStep.components,
    DEVICE_CATALOG_COMPONENT,
  );

  if (!deviceCatalogComponent) return { tableStructure, data: [], overallSetupFee: 0 };
  const deviceSummary = deviceCatalogComponent.fields.find(
    (field) => field.name === 'device_order_items_summary_field',
  );
  const deviceSummaryValues = deviceSummary?.value ?? [];
  const setupFee = deviceCatalogComponent.fields.find(
    (field) => field.name === 'device_order_summary_field',
  );

  return {
    tableStructure,
    data: deviceSummaryValues,
    overallSetupFee: setupFee?.value?.total_order_charge ?? 0,
  };
};
