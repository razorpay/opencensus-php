export const COD_ENGINE_TYPES = {
  LOCATION: 'location',
  SLAB_ELIGIBILITY: 'slab_eligibility',
  SLAB_RATE: 'slab_charges',
  PRODUCT: 'product',
};
export const SLAB_RATE_RADIO_INPUT = [
  {
    value: true,
    label: 'Charge COD Fee',
  },
  {
    value: false,
    label: 'Free COD',
  },
];

export const COD_ENGINES = {
  BASIC: 'Basic',
  ADVANCED: 'Advanced',
};

export const SETTINGS_OPTIONS = [
  {
    label: 'Basic',
    name: 'Basic',
  },
  {
    label: 'Advanced',
    name: 'Advanced',
  },
];

export const POPOVER_CONTENT = {
  setting_type:
    'Configure additional conditions to enable COD selectively across product-category, order value, zones, etc. You can also modify the COD fees for each of the slabs created.',
  slabs:
    'Orders with a subtotal within these ranges will be eligible to pay with Cash on Delivery. You can additionally add fees for each slab',
  zones: 'Create shipping zones where COD is eligible',
  categories:
    'Create Product categories to create custom rates or destination restrictions for groups of products.',
  zone_mapping: 'Manage applicable COD slabs and rates for different zones.',
  category_mapping: 'Set applicable rates and delivery restrictions for product categories',
};

export const MODAL_MODES = {
  EDIT: 'edit',
  ADD: 'add',
  CREATE: 'create',
};

export const SAVE_MODAL_TEXTS = {
  header: 'Save & apply settings?',
  desc: 'Once you save this configuration we will be able to set these rules of using COD for all customers. Are you sure you want to save these settings?',
  primaryCtaLabel: 'Save & apply settings',
  secondaryCtaLabel: 'Don’t save',
};

export const GLOBAL_KEY = 'International';

export const MAX_FEE_RULES = 20;
