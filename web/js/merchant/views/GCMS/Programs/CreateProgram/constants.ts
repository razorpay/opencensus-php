export const MODE = {
  CREATE: 1,
  EDIT: 2,
};

export const DENOMINATION_TYPE = {
  CUSTOMIZABLE: {
    value: 'range',
    helpText: 'Allow customers to choose an amount within your set range.',
    title: 'Customizable Denomination',
  },
  FIXED: {
    value: 'fixed',
    helpText: 'Let customers select from predefined values',
    title: 'Fixed Value',
  },
};

export const FILE_UPLOAD_OPTIONS = {
  BRAND_DESIGN: {
    value: 'brand',
    title: 'Generate Image',
    helpText: 'Automatically applies your default gift card style.',
  },
  CUSTOM: {
    value: 'custom',
    title: 'Upload an image',
    helpText: 'Choose and upload a gift card design of your choice.',
  },
};

export const GC_CARD_TYPE_VALUES = {
  NUMERIC: 'numeric',
  ALPHANUMERIC: 'alphanumeric',
};

export const GC_CARD_TYPE = {
  [GC_CARD_TYPE_VALUES.NUMERIC]: {
    value: GC_CARD_TYPE_VALUES.NUMERIC,
    title: 'Numeric',
  },
  [GC_CARD_TYPE_VALUES.ALPHANUMERIC]: {
    value: GC_CARD_TYPE_VALUES.ALPHANUMERIC,
    title: 'AlphaNumeric',
  },
};

export const isGiftCardDesignEnabled = false;

export const DEFAULT_GIFT_CARD_LENGTH = 16;

export const POSSIBLE_GIFT_CARD_LENGTHS = [10,11,12,13,14,15,16];
