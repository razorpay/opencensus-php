import { InstrumentsList, ShowNotificationType, User } from 'common/typings';
import { ExtraConfig } from 'merchant/components/SidebarV2/utils/Products';

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
  extraConfig: ExtraConfig;
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
  attributes?: Record<any, any>;
  id?: string;
};

export type ProductType = {
  item: ProductItem;
};

export type EligibleProducts = ProductItem & {
  apiCondition: boolean;
  additionalCondition: (args: EligibleProductsTypes, extraConfig: ExtraConfig) => boolean;
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

export interface ObjType {
  optionName: string;
  optionChosen: number;
  optionSet: number;
  optionSetTotal: number;
}

export type SearchableEntities =
  | 'Payments'
  | 'Refunds'
  | 'Orders'
  | 'Disputes'
  | 'Settlements'
  | 'Invoices'
  | 'PaymentLinks'
  | 'PaymentPages'
  | 'PaymentButtons'
  | 'Transfers'
  | 'Reversals'
  | 'Accounts'
  | 'Subscriptions'
  | 'Plans'
  | 'QRcode'
  | 'SmartCollect'
  | 'Customer'
  | 'Offers';

type PaymentEntityAttributeTypes = 'PaymentId' | 'PaymentStatus';
type RefundEntityAttributeTypes = 'RefundId' | 'RefundStatus';
type DisputeEntityAttributeTypes = 'DisputeId' | 'DisputeType' | 'DisputeState';
type SettlementEntityAttributeTypes = 'SettlementId' | 'SettlementStatus';
type OrderEntityAttributeTypes = 'OrderId' | 'OrderStatus';
type PaymentPageEntityAttributeTypes = 'PaymentPageUrl' | 'PaymentPageStatus';
type PaymentButtonsEntityAttributeTypes = 'PaymentButtonStatus';
type InvoiceEntityAttributeTypes = 'InvoiceId';
type GeneralAttributeTypes = 'Email' | 'PhoneNumber';
type PaymentLinkEntityAttributeTypes =
  | 'PaymentLinkId'
  | 'PaymentLinkBatchId'
  | 'PaymentLinkUrl'
  | 'PaymentLinkStatus';
type TransfersEntityAttributeTypes = 'TransferId' | 'TransferStatus' | 'TransferSettlementStatus';
type QRCodeEntityAttributeTypes = 'QRCodeId' | 'QRCodeStatus';
type AccountsEntityAttributeTypes = 'AccountId';
type ReversalsEntityAttributeTypes = 'ReversalId';
type SubscriptionsEntityAttributeTypes = 'SubscriptionId';
type PlanEntityAttributeTypes = 'PlanId';
type CustomerEntityAttributeTypes = 'CustomerId';
type SmartCollectEntityAttributeTypes = 'CustomerIdentifierId';
type OffersEntityAttributeTypes = 'OfferId';

export type EntityAttributeTypes =
  | PaymentEntityAttributeTypes
  | RefundEntityAttributeTypes
  | DisputeEntityAttributeTypes
  | SettlementEntityAttributeTypes
  | OrderEntityAttributeTypes
  | InvoiceEntityAttributeTypes
  | PaymentLinkEntityAttributeTypes
  | PaymentPageEntityAttributeTypes
  | PaymentButtonsEntityAttributeTypes
  | GeneralAttributeTypes
  | TransfersEntityAttributeTypes
  | ReversalsEntityAttributeTypes
  | AccountsEntityAttributeTypes
  | SubscriptionsEntityAttributeTypes
  | QRCodeEntityAttributeTypes
  | PlanEntityAttributeTypes
  | CustomerEntityAttributeTypes
  | SmartCollectEntityAttributeTypes
  | OffersEntityAttributeTypes;

export interface SearchableEntityType {
  id: SearchableEntities;
  route: string;
  icon: string;
  attributes: {
    [key in EntityAttributeTypes]?: string;
  };
}

export type SearchableEntitiesType = Record<SearchableEntities, SearchableEntityType>;

export type AttributeType =
  | 'entity_id'
  | 'entity_url'
  | 'entity_state'
  | 'entity_contact_number'
  | 'entity_email'
  | 'entity_secondary_id';

export interface attributeType {
  attributeId: EntityAttributeTypes;
  attributeType: AttributeType;
  matchWith: RegExp | string[];
  entities: SearchableEntities[];
}

export type entityAttributesTypes = Record<EntityAttributeTypes, attributeType>;

export type defaultEntityParamTypes = Record<SearchableEntities, string>;

export type statusKeywordsStoreType = Record<
  Exclude<
    SearchableEntities,
    | 'Invoices'
    | 'Reversals'
    | 'Accounts'
    | 'Subscriptions'
    | 'Plans'
    | 'Customer'
    | 'Offers'
    | 'SmartCollect'
  >,
  Record<string, string>
>;
