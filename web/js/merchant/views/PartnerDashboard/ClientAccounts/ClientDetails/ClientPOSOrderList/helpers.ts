import { ExperimentInfoType } from 'common/splitz/types';
import {
  deliveryAddressSchema,
  ORDER_STATUS_META_DATA,
  PRODUCT_DESCRIPTIONS,
} from 'merchant/views/PartnerDashboard/ClientAccounts/ClientDetails/ClientPOSOrderList/constants';
import SOUNDBOX from './DeviceConfig/Soundbox';
import {
  setItem as setLocalStorageItem,
  getItem as getLocalStorageItem,
} from 'common/utils/localStorage';
import STANDEEANDSTICKER from './DeviceConfig/StandeeAndSticker';
import {
  CartItem,
  ConstructProductDescription,
  DeliveryAddress,
  FetchProductOffers,
  getCartItemTotalArgs,
  getCartItemTotalReturnType,
  GetFilteredPricing,
  GetPricingValues,
  GetPricingValuesReturnType,
  GetProductDescriptionWithPricingPlan,
  GetProductFromProductDescriptions,
  OfferConfig,
  OrderDetailsItem,
  OrderStatusData,
  ProductDescription,
  ProductDescriptionPricing,
} from './types';

export const isValidFee = (value: number | null | undefined): boolean => {
  return value === 0 || (value !== undefined && value !== null);
};

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

export const getCartItemTotal = ({
  pricing,
  quantity,
  selectedPlan,
}: getCartItemTotalArgs): getCartItemTotalReturnType => {
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

export const getProductFromProductDescriptions = ({
  code,
  productDescriptions,
}: GetProductFromProductDescriptions): ProductDescription | null => {
  return productDescriptions.find(({ code: productCode }) => productCode === code) ?? null;
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

export const getProductOffers = ({
  abExperiments,
}: {
  abExperiments: ExperimentInfoType;
}): Record<string, OfferConfig> | null => {
  // @ts-expect-error
  const offerConfig = (abExperiments?.pos_onboarding?.variables?.offerConfig as string) ?? '';

  if (!offerConfig) return null;

  return JSON.parse(offerConfig) as Record<string, OfferConfig>;
};

export const fetchProductOffers = ({
  abExperiments,
}: {
  abExperiments: ExperimentInfoType;
}): FetchProductOffers => {
  // @ts-expect-error
  const isEnabled = abExperiments?.pos_onboarding?.variables?.offersEnabled === 'on';
  return {
    isEnabled,
    offers: isEnabled ? getProductOffers({ abExperiments }) : null,
  };
};
export const isValidAddresses = ({ addresses }: { addresses: DeliveryAddress[] }): boolean => {
  const isSchemaValid = addresses.every((currentAddress) =>
    deliveryAddressSchema.isValidSync(currentAddress),
  );

  const isDefaultAddressExsits = addresses.find(({ type }) => type === 'default');
  const selectedAddresses = addresses.filter(({ isSelected }) => isSelected);

  return !!(isSchemaValid && isDefaultAddressExsits && selectedAddresses.length === 1);
};
export const saveAddressInLocalStorage = ({
  userId,
  addresses,
}: {
  userId: string | undefined;
  addresses: DeliveryAddress[];
}): void => {
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

export const getCartFromLocalStorage = ({ userId }: { userId: string }): CartItem[] | null => {
  const key = `pos-user-${userId}-cart`;
  const persistedCartItems = getLocalStorageItem(key);
  const cartItems: CartItem[] | null = persistedCartItems ? JSON.parse(persistedCartItems) : null;
  const filteredValidCartItems =
    cartItems?.filter((item) => !!PRODUCT_DESCRIPTIONS?.[item.code]) ?? [];
  return filteredValidCartItems;
};

export const isPosSoundboxEnabled = ({ abExperiments }: { abExperiments: ExperimentInfoType }) => {
  // @ts-expect-error
  return abExperiments?.pos_soundbox?.variables?.result === 'on';
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
