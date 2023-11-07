import React, { useState, createContext } from 'react';
import {
  ModalContextValue,
  ErrorStates,
  ModalProviderProps,
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
    maxBudget: 0,
    isLimitedUseage: false,
  },
  discountDetails: {
    discountType: 'fixedAmount',
    discountValue: 0,
    minimumType: 'no_min_qty',
    minimumValue: 0,
    discountApplicableTo: 'products',
    discountedItemsList: [],
    discountedItemsDisplayList: [],
    maxDiscountValue: 0,
  },
  usageRestriction: {
    isLimitedUsage: false,
    maxUsage: 1,
    limitBy: 'phone',
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
  },
  bulkDiscountDetails: {
    discountSubType: 'fixedAmount',
    discountValue: 0,
    discountType: 'discountOnAll',
  },
  status: 'published',
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
  },
  discountOffered: {
    discountValue: null,
  },
  bulkDiscountDetails: {
    discountValue: null,
  },
};

export const ModalContext = createContext<ModalContextValue>({
  widgetsData: initialWidgetsData,
  setWidgetsData: () => {},
  errorStates: initialErrorStates,
  setErrorStates: () => {},
  resetWidgetsData: () => {},
  allCouponsList: [],
  setAllCouponsList: () => {},
});

export function ModalProvider({ children }: ModalProviderProps): JSX.Element {
  const [widgetsData, setWidgetsData] = useState(initialWidgetsData);
  const [errorStates, setErrorStates] = useState(initialErrorStates);
  const [allCouponsList, setAllCouponsList] = useState([]);

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
  };

  return <ModalContext.Provider value={contextValue}>{children}</ModalContext.Provider>;
}
