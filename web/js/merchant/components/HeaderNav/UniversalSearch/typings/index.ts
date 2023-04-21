import { InstrumentsList, ShowNotificationType, User } from 'common/typings';

interface WebsiteSectionData {
  isGracePeriodApplicable: boolean;
  isWebsiteSectionsApplicable: boolean;
}
export interface WebsiteSectionDetailsInterface {
  data: WebsiteSectionData;
  loading: boolean;
  error: boolean | any;
}

interface FeaturePayloadInterface {
  userId?: string;
  feature: string;
}

interface FeatureResponse {
  data: Record<string, boolean>;
  loading: boolean;
}
export type EligibleProductsTypes = Pick<
  UniversalSearchPropInterface,
  'user' | 'instruments' | 'mode' | 'websiteSectionDetailsData'
> & {
  allowCFBInternational: boolean;
  hasEnrolled: boolean | null;
};

type Tags = {
  value: string;
};

export type ProductItem = {
  title: string;
  url: string;
  tags: Tags[];
  icon: string;
  group?: string[];
};

export type ProductType = {
  item: ProductItem;
};

export type EligibleProducts = ProductItem & {
  apiCondition: boolean;
  additionalCondition: (args: EligibleProductsTypes) => boolean;
};

export interface UniversalSearchPropInterface {
  isMobile: boolean;
  user: Required<User>;
  mode: string;
  instruments: InstrumentsList;
  isInstrumentLoading: boolean;
  websiteSectionDetailsData: WebsiteSectionDetailsInterface;
  fetchMerchantWebsiteDetailsFn: () => Promise<void>;
  fetchFeatureByNameFn: (payload: FeaturePayloadInterface) => Promise<void>;
  fetchMerchantInstrumentsFn: () => Promise<void>;
  fetchRequestedInstrumentsFn: () => Promise<void>;
  fetchEnrollmentStatus: () => Promise<void>;
  showNotificationFn: ShowNotificationType;
  setLoadingFn: () => Promise<void>;
  featureStatusConfig: FeatureResponse;
  enrollmentStatus: {
    loading: boolean;
    hasEnrolled: boolean | null;
    message: string | null;
    error: unknown;
  };
}

export interface CommonStateProps {
  setSearch: (args: string) => void;
  setFocussed: (args: boolean) => void;
  isDeviceInBreakpoint: boolean;
  show: boolean;
  searchQuery: string;
}

export type ObjType = Record<string, unknown>;
