import lazy from 'merchant/routes/LazyLoader';

import { ShippingEngineRoute } from './context/RouteContext';

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
