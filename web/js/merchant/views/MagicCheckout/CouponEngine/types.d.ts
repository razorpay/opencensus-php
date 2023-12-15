import { ReactNode, Dispatch, SetStateAction } from 'react';

interface CustomerData {
  type: string;
  name: string;
  source_type: string;
  source: string;
}

interface DiscountedItemsList {
  product_id?: string;
  product_image_url?: string;
  product_name?: string;
  variants?: string[];
  id?: string;
  title?: string;
  products_count?: string | number;
}

interface CouponEligibility {
  customerGroup: string;
  customerList: string[];
  customerDetailsType: string;
  customerDisplayList: CustomerData | Record<string, unknown>;
}

interface CouponValidity {
  startDate: string;
  endDate: string;
  startTime: string;
  endTime: string;
  maxBudget: number | string;
  isLimitedUsage: boolean;
}

interface DiscountDetails {
  discountType: string;
  discountValue: number;
  minimumType: string;
  minimumValue: number | string;
  discountApplicableTo: string;
  discountedItemsList: string[];
  discountedItemsDisplayList: DiscountedItemsList[];
  maxDiscountValue: number | string;
}

interface UsageRestriction {
  isLimitedUsage: boolean;
  isRestrictedTotalUsage: boolean;
  maxUsage: number;
  limitBy: string;
  total: number;
}

interface ProductsPurchased {
  minimumType: string;
  minimumValue: number | string;
  discountApplicableTo: string;
  discountedItemsList: string[];
  discountedItemsDisplayList: DiscountedItemsList[];
}

interface DiscountOffered {
  discountType: string;
  discountValue: number;
  discountApplicableTo: string;
  discountedItemsList: string[];
  discountedItemsDisplayList: DiscountedItemsList[];
  productsOffered: string | number;
  hasLimitedUseagePerOrder: boolean;
  maxUsagePerOrder: string | number;
  discountSubType: string;
}

interface BulkDiscountDetails {
  discountSubType: string;
  discountValue: number;
  discountType: string;
  maxUsagePerOrder: string | number;
  hasLimitedUseagePerOrder: boolean;
}

interface CouponDetails {
  code: string;
  description: string;
  display: boolean;
  autoapply: boolean;
  prepaidMethodsOnly: boolean;
}

export interface ErrorStates {
  couponDetails: {
    code: null | string;
    description: null | string;
  };
  discountDetails: {
    discountValue: null | string;
  };
  productsPurchased: {
    minimumValue: null | string;
  };
  couponValidity: {
    startDate: null | string;
    endDate: null | string;
    couponTime: null | string;
  };
  discountOffered: {
    discountValue: null | string;
  };
  bulkDiscountDetails: {
    discountValue: null | string;
  };
}

export interface ModalContextValue {
  widgetsData: {
    couponEligibility: CouponEligibility;
    couponValidity: CouponValidity;
    discountDetails: DiscountDetails;
    usageRestriction: UsageRestriction;
    productsPurchased: ProductsPurchased;
    discountOffered: DiscountOffered;
    couponDetails: CouponDetails;
    bulkDiscountDetails: BulkDiscountDetails;
    status: string;
    id?: string;
    source: string | null;
  };
  setWidgetsData: Dispatch<SetStateAction<ModalContextValue['widgetsData']>>;
  errorStates: ErrorStates;
  setErrorStates: Dispatch<SetStateAction<ErrorStates>>;
  resetWidgetsData: () => void;
  allCouponsList: any;
  setAllCouponsList: Dispatch<SetStateAction<any>>;
}

export interface ModalProviderProps {
  children: ReactNode;
}
