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
