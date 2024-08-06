export enum ANALYTICS_EVENTS {
  ICON_CLICKED = 'Icon Clicked',
  WEBSITE_CTA_CLICKED = 'Website Cta Clicked',
  FORM_FIELD_SELECTED = 'Form Field Selected',
  LINK_CLICKED = 'Link Clicked',
  FORM_FIELD_FILL_INDICATED = 'Form Field Fill Initiated',
  FORM_PAGE_VIEWED = 'Form Page Viewed',
  PAGE_READ_SUCCESS = 'Page Read Success',
  TRANSITION_SCREEN_LOADED = 'Transition Screen Loaded',
  PAGE_VIEWED = 'Page Viewed',
}

export enum L1_FUNNED_STAGE {
  DEVICE_EXPLORATION = 'Device Exploration',
  MERCHANT_DETAILS = 'Merchant Details',
}

export enum L2_FUNNEL_STAGE {
  POS_PRODUCT_DESCRIPTION = 'POS Product Description',
  PAGE_VIEW = 'Page View',
}

export enum FIELD_TYPES {
  TEXTBOX = 'Textbox',
  CHECKBOX = 'Checkbox',
}

export enum PAGE_TYPES {
  AGENT_DASHBOARD = 'Agent Dashboard',
  MERCHANT_DETAILS = 'Merchant Details',
  POS_PRODUCT_DESCRIPTION = 'POS Product Description',
  PAGE_VIEW = 'Page View',
}

export enum STATUS {
  SUCCESS = 'success',
  FAILURE = 'failure',
}

export interface EventProperties {
  type?: string;
  section?: string;
  subSection?: string;
  l1FunnelStage?: L1_FUNNED_STAGE;
  l2FunnelStage?: L2_FUNNEL_STAGE;
  fieldType?: FIELD_TYPES;
  status?: STATUS;
  errorMessage?: string;
  pageType?: PAGE_TYPES;
}
