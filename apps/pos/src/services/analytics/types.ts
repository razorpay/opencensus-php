export enum ANALYTICS_EVENTS {
  ICON = 'Icon',
  WEBSITE_CTA = 'Website Cta',
  FORM_FIELD = 'Form Field',
  FORM_PAGE_RESPONSE = 'Form Page Response',
  LINK = 'Link',
  FORM_FIELD_FILL = 'Form Field Fill',
  FORM_PAGE = 'Form Page',
  PAGE_READ = 'Page Read',
  TRANSITION_SCREEN = 'Transition Screen',
  PAGE = 'Page',
  IMAGE = 'Image',
  DOCUMENT_UPLOAD = 'Document Upload',
}

export enum ANALYTICS_ACTIONS {
  CLICKED = 'Clicked',
  SELECTED = 'Selected',
  FILLED = 'Filled',
  RECEIVED = 'Received',
  VIEWED = 'Viewed',
  LOADED = 'Loaded',
  INITIATED = 'Initiated',
  SUCCESS = 'Success',
  RESPONSE_RECEIVED = 'Response Received',
}

export enum L1_FUNNEL_STAGE {
  DEVICE_EXPLORATION = 'Device Exploration',
  MERCHANT_DETAILS = 'Merchant Details',
  MERCHANT_SIGNUP = 'Merchant Signup',
  DEVICE_ORDERING = 'Device Ordering',
  DEVICE_EDITING = 'Device Editing',
  ORDER_CONFIRMATION = 'Order Confirmation',
  ORDER_DELIVERY_ADDRESS = 'Order Delivery Address',
  CHECKOUT_PAGE = 'Checkout Page',
  CHECKOUT_STATUS = 'Checkout Status',
  PAYMENT_METHOD_AND_SERVICE_SELECTION = 'Payment Method & Service Selection',
  AGGREGATOR_MODEL = 'Aggregator Model',
  DIRECT_MODEL = 'Direct Model',
  MDR_RATES_AFFORDABILITY_CATEGORY = 'MDR Rates/Affordability Category',
  VAS_CATEGORY = 'VAS Category',
  ADDITIONAL_DETAILS = 'Additional Details',
  POST_CHECKOUT = 'Post checkout',
  MERCHANT_ONBOARDING = 'Merchant Onboarding',
  AGREEMENT_SIGNING = 'Agreement Signing',
  DEVICE_DEPLOYMENT = 'Device Deployment',
}

export enum L2_FUNNEL_STAGE {
  POS_PRODUCT_DESCRIPTION = 'POS Product Description',
  POS_PRODUCT_EDITING = 'POS Product Editing',
  PAGE_VIEW = 'Page View',
  AGENT_DASHBOARD_HOMESCREEN = 'Agent Dashboard Homescreen',
  CONTACT_DETAILS_VERIFICATION = 'Contact Details Verification',
  MOBILE_NUMBER_ENTRY = 'Mobile Number Entry',
  CTR = 'CTR',
  MOBILE_OTP_VERIFICATION = 'Mobile OTP Verification',
  POS_PRODUCT_CONFIRMATION = 'POS Product Confirmation',
  PAYMENT_DETAILS = 'Payment Details',
  DELIVERY_ADDRESS_CONFIRMATION = 'Delivery Address Confirmation',
  CHECKOUT_CONFIRMATION = 'Checkout Confirmation',
  RENTAL_ADVANCE = 'Rental Advance',
  PURCHASE_PAPER_ROLL = 'Purchase Paper Roll',
  SETUP_FEE = 'Setup Fee',
  MONTLY_RENTAL_FEE = 'Monthly Rental Fee',
  ONBOARDING_MODEL = 'Onboarding Model',
  ONBOARDING_OPTION_AGGREGATOR = 'Option chosen aggregator',
  ONBOARDING_OPTION_DIRECT = 'Option chosen direct',
  AGGREGATOR_MODEL = 'Aggregator Model',
  DIRECT_MODEL = 'Direct Model',
  MDR_RATES_AFFORDABILITY_CATEGORY = 'MDR Rates/Affordability Category',
  VAS_CATEGORY = 'VAS Category',
  DEBIT_CARD_RUPAY_MDR_RATE_FIELD = 'debit_card_rupay_mdr_rate_field',
  DEBIT_CARD_VISA_MASTERCARD_MAESTRO_GREATER_THAN_2K_MDR_RATE_FIELD = 'debit_card_visa_mastercard_maestro_greater_than_2k_mdr_rate_field',
  DEBIT_CARD_VISA_MASTERCARD_MAESTRO_LESS_THAN_2K_MDR_RATE_FIELD = 'debit_card_visa_mastercard_maestro_less_than_2k_mdr_rate_field',
  CREDIT_CARD_MDR_RATE_FIELD = 'credit_card_mdr_rate_field',
  PREPAID_B2B_CORPORATE_CHANNEL_INTERNATIONAL_CARD_MDR_RATE_FIELD = 'prepaid_b2b_corporate_channel_international_card_mdr_rate_field',
  UPI_MDR_RATE_FIELD = 'upi_mdr_rate_field',
  VAS_CC_EMI_RATE_FIELD = 'vas_cc_emi_rate_field',
  VAS_DC_EMI_RATE_FIELD = 'vas_dc_emi_rate_field',
  VAS_CC_EMI_RATE_ENABLED_FIELD = 'vas_cc_emi_rate_enabled_field',
  VAS_DC_EMI_RATE_ENABLED_FIELD = 'vas_dc_emi_rate_enabled_field',
  CUSTOM_RATES_DOCUMENTS_FIELD = 'custom_rates_documents_field',
  PREVIOUS_CUSTOM_RATES_DOCUMENTS_FIELD = 'previous_custom_rates_documents_field',
  CUSTOM_RATES_ENABLED_FIELD = 'custom_rates_enabled_field',
  MDR_VAS_PRICING_FIELD = 'mdr_vas_pricing_field',
  NACH = 'Nach',
  ADDITIONAL_SALES_COMMENTS = 'Additional Sales Comments',
  TAXATION_AND_COMPLIANCE = 'Taxation And Compliance',
  PAYMENT_CONFIRMATION_LOADING = 'Payment Confirmation Loading',
  PAYMENT_PENDING = 'Payment pending',
  ORDER_CONFIRMATION = 'Order Confirmation',
  ADDITIONAL_DETAILS = 'Additional Details',
  SIGNING_MODE = 'Signing Mode',
  ONLINE_METHOD = 'Online Method',
  OFFLINE_METHOD = 'Offline Method',
  AGREEMENT_SIGNING = 'Agreement Signing',
  MERCHANT_SIGNING_ONLINE = 'Merchant Signing-online',
  MERCHANT_ONBOARDING = 'MERCHANT_ONBOARDING',
  DEVICE_DEPLOYMENT = 'Device Deployment',
  EXPLORE_DEVICE_DEPLOYMENT = 'Explore Device Deployment',
}

export enum FIELD_TYPES {
  TEXTBOX = 'Textbox',
  CHECKBOX = 'Checkbox',
  RADIO = 'Radio',
  RADIO_BUTTON = 'Radio Button',
  DROPDOWN = 'Dropdown',
}

export enum PAGE_TYPES {
  AGENT_DASHBOARD = 'Agent Dashboard',
  MERCHANT_DETAILS = 'Merchant Details',
  POS_PRODUCT_DESCRIPTION = 'POS Product Description',
  PAGE_VIEW = 'Page View',
  DEVICE_EDITING = 'Device Editing',
  ORDER_CONFIRMATION = 'Order Confirmation',
  PAYMENT_DETAILS = 'Payment Details',
  ORDER_DELIVERY_ADDRESS = 'Order Delivery Address',
  CHECKOUT_PAGE = 'Checkout Page',
  ADDITIONAL_SALES_COMMENTS = 'Additional Sales Comments',
  POST_CHECKOUT = 'Post Checkout',
  MERCHANT_SIGNING_ONLINE = 'Merchant Signing-online',
  AGREEMENT_DETAILS_SUCCESS = 'Agreement Details Success',
}

export enum STATUS {
  SUCCESS = 'success',
  FAILURE = 'failure',
}

export interface EventProperties {
  type?: string;
  section?: string;
  subSection?: string;
  l1FunnelStage?: L1_FUNNEL_STAGE;
  l2FunnelStage?: L2_FUNNEL_STAGE;
  fieldType?: FIELD_TYPES;
  status?: STATUS;
  errorMessage?: string;
  pageType?: PAGE_TYPES;
  label?: string;
  formName?: string;
  fieldName?: string;
  noOfItems?: number | string;
  documentUploaded?: string;
}
