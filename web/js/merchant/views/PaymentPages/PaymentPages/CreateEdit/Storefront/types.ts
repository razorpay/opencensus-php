import {
  IPaymentPagesProduct,
  PaymentPagesStorefrontType,
} from 'merchant/reducers/paymentPages/storefront';
import {
  ICategories,
  SocialMediaHandle,
  SocialMediaHandles,
} from 'merchant/reducers/paymentPages/types';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { BladeFileList } from '@razorpay/blade/components';

export type TripleState = -1 | 0 | 1;

type ISupportDetails =
  | {
      id: string;
      email: string;
      phone: string;
      // type: string;
      // policy: any;
      // url: string;
    }
  | Record<string, never>;

export type ModeTypes = 'live' | 'test';
export interface IStorefrontProps extends RouteComponentProps {
  id?: string;
  isMobile: boolean;
  // Ideally the value should be inferred from redux state (avoiding for now, as we're using JS everywhere)
  storefront: PaymentPagesStorefrontType;
  user: any;
  config: any;
  org: any;
  globalSupportDetails: ISupportDetails;
  fetchSupportDetail: () => Promise<{
    data: ISupportDetails;
  }>;
  editStorefront: (key: string, value: any) => void;
  editStorefrontDeepMerge: (payload) => void;
  openModal: (data: any) => void;
  closeModal: () => void;
  fetchCategories: () => void;
  showNotification: (data: any) => void;
  removeProduct: (id: string) => void;
  previewDevice: (id: boolean) => void;
  resetStorefront: () => void;
  fetchStorefront: (id: string, isIntentDuplicate?: boolean) => Promise<void>;
  addProduct: (product) => void;
  editProduct: (product) => void;
  addCategory: (category) => void;
  mode: ModeTypes;
}

export interface IHostedPagesMerchant {
  id: string;
  name: string;
  image: string;
  brand_color: string;
  brand_text_color: string;
  support_details: {
    support_email: string;
    support_mobile: string;
  };
}

export interface IHostedPagesProduct {
  id: string;
  name: string;
  description: string;
  images: string[];
  selling_price: number;
  discounted_price?: number;
  stock: number;
  stock_available: number;
  stock_sold: number;
  status: string;
  categories: ICategories;
}

type ProductFunction = (id: string) => void;
export type TDropdown = string | null;

export interface ISampleProducts {
  removeProduct: () => void;
  isMobile: boolean;
  children: React.ReactElement;
}

export interface IProductItems {
  data: IPaymentPagesProduct[];
  editProduct?: ProductFunction;
  removeProduct?: ProductFunction;
  isMobile: boolean;
}

export interface IProductItem {
  product: IPaymentPagesProduct;
  currentDropdown: TDropdown;
  setCurrentDropdown: (id: TDropdown) => void;
  editProduct?: (id: string) => void;
  removeProduct?: (id: string) => void;
  isMobile: boolean;
}

export interface IProductSection {
  title?: string;
  data: IPaymentPagesProduct[];
  children?: React.ReactNode;
  className?: string;
  editProduct?: ProductFunction;
  removeProduct?: ProductFunction;
  isMobile: boolean;
}

export interface ISelectProductDrawer {
  handleClose: () => void;
  storefront: PaymentPagesStorefrontType;
  fetchProducts: (
    count?: number,
  ) => Promise<{ data?: { items: IPaymentPagesProduct[] }; errors?: any }>;
  addProducts: (data: IPaymentPagesProduct[]) => void;
  openAddModal: () => void;
  showNotification: (data: any) => void;
  isCreate?: Boolean;
}

export interface ICheckbox {
  product_name: string;
  id: string;
  checked: boolean;
  disabled: boolean;
  images: Array<any>;
  amount: string;
  discounted_amount: string;
}

interface OpenModalOptions {
  size?: 'regular' | 'small' | 'medium' | 'med-large' | 'large' | 'xlarge';
  component: JSX.Element;
  className?: string;
  overlayStyles?: Record<string, string>;
  isNew?: boolean;
}
export type OpenModalType = (data: OpenModalOptions) => void;

interface ShowNotificationOptions {
  type: 'success' | 'error';
  message: string | JSX.Element;
  closeTimeout?: number;
}

export type ShowNotificationType = (data: ShowNotificationOptions) => void;

export type FeedbackColors = 'information' | 'negative' | 'neutral' | 'notice' | 'positive';
export interface FlipOptions {
  horizontal: boolean;
  vertical: boolean;
}

export interface PixelCrop {
  x: number;
  y: number;
  width: number;
  height: number;
}

export interface AlertState {
  showBannerAlert: boolean;
  showSocialHandleAlert: boolean;
}

export interface SocialHandleDrawerProps {
  handleClose: () => void;
  isMobile: boolean;
  addSocialHandle: (payload: SocialMediaHandle) => void;
  storefront: PaymentPagesStorefrontType;
  storefrontId: string;
  reorderSocialHandle: (payload: any) => void;
  updateSocialHandle: (payload: SocialMediaHandle) => void;
  deleteSocialHandle: (payload: { platform: string }) => void;
}

export interface SocialHandle {
  name: string;
  label: string;
  src: string;
  inputLabel: string;
  inputPlaceholder: string;
}

export interface SocialHandleModalProps {
  saveSocialHandle: () => void;
  storefront?: PaymentPagesStorefrontType;
  inputVal: string;
  selectedHandle: SocialHandle | null;
  updateInputValue: (e: any) => void;
  cancelSocialHandleOperation: () => void;
  uploadedFile: File | null;
  setUploadedFile: React.Dispatch<React.SetStateAction<File | null>>;
  isSaving: boolean;
  uploadedLogo: string;
  setUploadedLogo: React.Dispatch<React.SetStateAction<string>>;
  isSaveDisabled: boolean;
  setSelectedHandle: React.Dispatch<React.SetStateAction<SocialHandle | null>>;
}

export interface AddDetailsFooterButtonsProps {
  onCancel: () => void;
  onSave: (inputVal?: string,uploadedFile?: File | null) => void;
  inputVal?: string;
  isSaveDisabled?: boolean;
  isSaving?: boolean;
  uploadedFile?: File | null;
}
export interface ModalFooterButtonsProps {
  onCancel: () => void;
  onSave: () => void;
  isSaveDisabled?: boolean;
  isSaving?: boolean;
}

export interface ISingleSocialHandleProps {
  id: string;
  platform: string;
  logo_url: string;
  showDeleteConfirmation: (val: string) => void;
  editSocialHandle: (val: string) => void;
}

export interface SocialHandleListProps {
  reorderSocialHandle: (payload: any) => void;
  isMobile: boolean;
  socialHandleList: SocialMediaHandles;
  editSocialHandle: (handle: string) => void;
  showDeleteConfirmation: (handle: string) => void;
}

export interface SocialHandleDrawerProps {
  handleClose: () => void;
  isMobile: boolean;
  addSocialHandle: (payload: SocialMediaHandle) => void;
  storefront: PaymentPagesStorefrontType;
  storefrontId: string;
  reorderSocialHandle: (payload: any) => void;
  updateSocialHandle: (payload: SocialMediaHandle) => void;
  deleteSocialHandle: (payload: { platform: string }) => void;
}

export interface SocialHandleDrawerViewProps {
  storefront: PaymentPagesStorefrontType;
  isMobile: boolean;
  handleClose: () => void;
  showSelectModal: boolean;
  selectedHandle: SocialHandle | null;
  setSelectedHandle: React.Dispatch<React.SetStateAction<SocialHandle | null>>;
  saveSocialHandle: () => void;
  cancelSocialHandleOperation: () => void;
  editSocialHandle: (platform: string) => void;
  openSocialHandleSelector: () => void;
  reorderSocialHandle: (payload: any) => void;
  isSaving: boolean;
  deleteSocialHandle: (payload: { platform: string }) => void;
}

export interface AddDetailsContentProps {
  selectedHandle: SocialHandle;
  uploadedFile: File | null;
  setUploadedFile: React.Dispatch<React.SetStateAction<File | null>>;
  uploadedLogo: string;
  setUploadedLogo: React.Dispatch<React.SetStateAction<string>>;
  inputVal: string;
  handleInputChange: (e: any) => void;
  isMobile: boolean;
}
