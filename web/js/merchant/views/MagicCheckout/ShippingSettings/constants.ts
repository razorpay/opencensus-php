import lazy from 'merchant/routes/LazyLoader';

import { ShippingEngineRoute } from './context/RouteContext';
import { ModalState } from 'merchant/reducers/magicCheckout/shippingEngine/types';

const PreviewSettings = lazy(
  () => import(/* webpackChunkName: "MagicShippingSettings" */ './Preview'),
);

const ProfileSettings = lazy(
  () => import(/* webpackChunkName: "MagicShippingSettings" */ './ProfileSettings'),
);

export const routes: Record<ShippingEngineRoute, () => JSX.Element> = {
  preview: PreviewSettings,
  profile: ProfileSettings,
};

export const ADD_PROFILE = 'add-profile';

export const PROFILE_TYPES = { DEFAULT: 'default', GENERAL: 'general' };

export const MODAL_TEXTS = {
  PROFILE_NOT_CONFIGURED: {
    header: 'Shipping settings',
    description:
      'All shipping profiles are not configured. Please configure them to use Magic shipping',
  },
  PREVENT_DELETE_ZONE: {
    header: 'Shipping settings',
    description:
      'At least one zone is required for configuring Shipping settings. Toggle Shipping settings off if you wish to disable Magic Shipping',
  },
};

export const RATE_OPTIONS = [
  {
    label: 'Amount',
    name: 'amount',
  },
  {
    label: 'Weight',
    name: 'weight',
  },
];

export const AMOUNT_OPTIONS = [
  {
    label: 'Range',
    name: 'range',
  },
];

export const RATE_TYPES = {
  AMOUNT: 'amount',
  WEIGHT: 'weight',
};

export const DISPLAY_MESSAGES = {
  process: 'The file is being uploaded. Please wait as this may take some time.',
  success: 'The file has been uploaded successfully.',
  error: 'There was an error while uploading the file. Please try again after some time.',
  exceed: 'The file size exceeds the maximum size limit. Please upload a smaller file.',
};

export const SAMPLE_FILE_URL =
  'https://cdn.razorpay.com/static/assets/magic-checkout/sample_pincodes_upload.csv';

export const FILE_UPDATE_NOTIFICATION_MSG =
  'Uploading a new file, will delete the older one and new records will be saved.';

type MODAL_TYPES = {
  [key: string]: ModalState;
};
export const MODAL_TYPES: MODAL_TYPES = {
  FILE_UPLOAD: 'FILE_UPLOAD',
  MANUAL: 'MANUAL',
};

export const OVERLAPPING_PINCODE_ERROR = 'BAD_REQUEST_ERROR:overlapping_location:';
