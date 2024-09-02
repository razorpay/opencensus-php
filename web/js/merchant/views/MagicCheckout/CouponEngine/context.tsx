import React, { useState, createContext } from 'react';
import {
  ModalContextValue,
  ErrorStates,
  ModalProviderProps,
  ShopifyCouponSyncResponse,
} from 'merchant/views/MagicCheckout/CouponEngine/types';
import moment from 'moment';

const initialWidgetsData: ModalContextValue['widgetsData'] = {
  couponEligibility: {
    customerGroup: 'allCustomers',
    customerList: [],
    customerDetailsType: '',
    customerDisplayList: {},
  },
  couponValidity: {
    startDate: moment().format('YYYY-MM-DD'),
    endDate: moment().add(1, 'days').format('YYYY-MM-DD'),
    startTime: moment().add(2, 'hours').format('h:mm a'),
    endTime: moment().add(1, 'days').endOf('day').format('h:mm a'),
    maxBudget: '',
    isLimitedUsage: false,
  },
  discountDetails: {
    discountType: 'fixedAmount',
    discountValue: 0,
    minimumType: 'no_min_qty',
    minimumValue: 0,
    discountApplicableTo: 'products',
    discountedItemsList: [],
    discountedItemsDisplayList: [],
    maxDiscountValue: '',
    hasLimitedUseagePerOrder: false,
  },
  usageRestriction: {
    isLimitedUsage: false,
    isRestrictedTotalUsage: false,
    maxUsage: 1,
    limitBy: 'phone',
    total: 1,
  },
  productsPurchased: {
    minimumType: 'min_qty',
    minimumValue: 1,
    discountApplicableTo: 'products',
    discountedItemsList: [],
    discountedItemsDisplayList: [],
  },
  discountOffered: {
    discountType: 'monetary-discount',
    discountValue: 0,
    discountApplicableTo: 'products',
    discountedItemsList: [],
    productsOffered: 1,
    hasLimitedUseagePerOrder: false,
    maxUsagePerOrder: 1,
    discountedItemsDisplayList: [],
    discountSubType: 'fixedAmount',
  },
  couponDetails: {
    code: '',
    description: '',
    display: true,
    autoapply: false,
    prepaidMethodsOnly: false,
    couponDiscoveryEnabled: false,
  },
  bulkDiscountDetails: {
    discountSubType: 'fixedAmount',
    discountValue: 0,
    discountType: 'discountOnAll',
    maxUsagePerOrder: 1,
    hasLimitedUseagePerOrder: false,
  },
  combineCoupons: {
    shouldCombineFreeShippingCoupon: false,
    shouldCombineOtherAmountOffProductCoupons: false,
    shouldCombineAmountOffOrderCoupon: false,
    shouldCombineBulkDiscountCoupon: false,
    shouldCombineBxGyDiscountCoupon: false,
  },
  status: 'published',
  source: null,
};

const initialErrorStates: ErrorStates = {
  couponDetails: {
    code: null,
    description: null,
  },
  discountDetails: {
    discountValue: null,
  },
  productsPurchased: {
    minimumValue: null,
  },
  couponValidity: {
    startDate: null,
    endDate: null,
    couponTime: null,
    maximumBudget: null,
  },
  discountOffered: {
    discountValue: null,
  },
  bulkDiscountDetails: {
    discountValue: null,
  },
  usageRestriction: {
    total: null,
    maxUsage: null,
    enforceUsageRestriction: null,
  },
};

const initialShopifySyncStatus: ShopifyCouponSyncResponse = {
  status: 'not-started',
};

export const ModalContext = createContext<ModalContextValue>({
  widgetsData: initialWidgetsData,
  setWidgetsData: () => {},
  errorStates: initialErrorStates,
  setErrorStates: () => {},
  resetWidgetsData: () => {},
  allCouponsList: [],
  setAllCouponsList: () => {},
  hasCouponScreenLoadedOnce: true,
  setHasCouponScreenLoadedOnce: () => {},
  shopifySyncStatus: initialShopifySyncStatus,
  setShopifySyncStatus: () => {},
});

export function ModalProvider({ children }: ModalProviderProps): JSX.Element {
  const [widgetsData, setWidgetsData] = useState(initialWidgetsData);
  const [errorStates, setErrorStates] = useState(initialErrorStates);
  const [hasCouponScreenLoadedOnce, setHasCouponScreenLoadedOnce] = useState(true);
  const [allCouponsList, setAllCouponsList] = useState([]);
  const [shopifySyncStatus, setShopifySyncStatus] = useState(initialShopifySyncStatus);

  const resetWidgetsData = () => {
    setWidgetsData(initialWidgetsData);
    setErrorStates(initialErrorStates);
  };

  const contextValue: ModalContextValue = {
    widgetsData,
    setWidgetsData,
    errorStates,
    setErrorStates,
    resetWidgetsData,
    allCouponsList,
    setAllCouponsList,
    hasCouponScreenLoadedOnce,
    setHasCouponScreenLoadedOnce,
    shopifySyncStatus,
    setShopifySyncStatus,
  };

  return <ModalContext.Provider value={contextValue}>{children}</ModalContext.Provider>;
}
