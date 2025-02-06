import { DeviceConfig, DeviceOrderSummaryItem, PlanConfig } from 'apps/pos/src/app/types/modular';

export enum MODULAR_DEVICE_FIELDS {
  DEVICE_NAME = 'device_item_name_field',
  DEVICE_PLAN = 'device_item_plan_field',
  DEVICE_QUANTITY = 'device_item_quantity_field',
  DEVICE_SETUP_FEE_TYPE = 'device_item_setup_fee_type_field',
  DEVICE_RENTAL_TYPE = 'device_item_rental_charges_type_field',
  DEVICE_ADVANCE_RENTAL_FEE = 'device_item_advanced_rental_field',
  DEVICE_SETUP_CUSTOM_FEE_AMOUNT = 'device_item_custom_setup_fee_field',
  DEVICE_RENTAL_CUSTOM_AMOUNT = 'device_item_custom_rental_charges_field',
  DEVICE_ADVANCE_RENTAL_PERIOD_FIELD = 'device_item_advanced_rental_periods_field',
  DEVICE_ADD_TO_CART_FIELD = 'device_add_to_cart_field',
  DEVICE_CONFIRM_DEVICE_FIELD = 'device_selection_completion_field',
  DEVICE_ID = 'device_item_id_field',
  DEVICE_EDIT_CART_FIELD = 'device_edit_cart_field',
  DEVICE_DELETE_PRODUCT_FIELD = 'device_delete_from_cart_field',
  DEVICE_CART_ID_FIELD = 'device_cart_item_id_field',
  MODULAR_CALLBACK = 'modular_callback',
  DEVICE_CUSTOM_PRICING_DOC = 'device_custom_pricing_documents_field',
  DEVICE_DELIVERY_ADDRESS_FIELD = 'device_delivery_address_field',
  DEVICE_ORDER_CONFIRMATION_FIELD = 'device_order_confirmation_field',
  DEVICE_ORDER_QR_AMOUNT = 'qr_payment_amount_field',
  DEVICE_ORDER_PAYMENT_LINK_AMOUNT_FIELD = 'payment_link_payment_amount_field',
  DEVICE_QR_PAYMENT_STATUS_CHECK = 'check_qr_status_field',
  DEVICE_SELECTION_STEP = 'device_selection_step',
  DEVICE_CART_COMPONENT = 'device_cart_component',
  DEVICE_CATALOG_COMPONENT = 'device_catalogue_component',
  DEVICE_ORDER_ITEMS_SUMMARY_FIELD = 'device_order_items_summary_field',
  DEVICE_ORDER_SUMMARY_FIELD = 'device_order_summary_field',
  DEVICE_CUSTOM_PRICING_DOCS = 'device_custom_pricing_documents_field',
  DEVICE_QR_CODE_COMPONENT = 'qr_code_component',
  DEVICE_QR_CODE_COMPONENT_V2 = 'qr_code_component_v2',
  DEVICE_QR_IMAGE_CONTENT_FIELD = 'qr_image_content_field',
  DEVICE_PAYMENT_AMOUNT_FIELD = 'qr_payment_amount_field',
  DEVICE_DELIVERY_ADDRESS_COMPONENT = 'device_delivery_address_component',
  DEVICE_PAYMENT_METHOD_COMPONENT = 'device_payment_method_component',
  DEVICE_PAYMENT_COMPONENT = 'device_payment',
  DEVICE_CHECK_FOR_ORDER_COMPLETION = 'check_for_order_completion',
  PARTNER_DEVICE_CATALOG_COMPONENT = 'partner_device_catalogue_component',
  PARTNER_DEVICE_CART_COMPONENT = 'partner_device_cart_component',
  NACH_FORM_COMPONENT = 'nach_form_component',
  DEVICE_CUSTOM_RATES_APPLICABLE = 'device_custom_rates_applicable_field',
  DEVICE_CUSTOM_RATES_DOCUMENTS = 'custom_device_charges_proof',
  DEVICE_SALES_ASSISTED_PAYMENT_LINK_COMPONENT = 'sales_assisted_payment_link_component',
  DEVICE_PAYMENT_OPTIONS_COMPONENT = 'payment_options_component',
  DEVICE_PAYMENT_OPTIONS_FIELD = 'payment_options_field',
  DEVICE_CANCEL_PAYMENT_LINK_FIELD = 'cancel_payment_link_field',
  DEVICE_QR_CODE = 'qr_code',
  DEVICE_PAYMENT_LINK = 'payment_link',
  DEVICE_PAYMENT_LINK_CONFIRMATION_COMPONENT = 'payment_link_confirmation_component',
  DEVICE_PAYMENT_LINK_REFERENCE_ID = 'payment_link_reference_id',
  DEVICE_PAYMENT_LINK_NOTIFY_EMAIL = 'payment_link_notify_via_email_field',
  DEVICE_PAYMENT_LINK_NOTIFY_MOBILE = 'payment_link_notify_via_mobile_field',
  DEVICE_PAYMENT_LINK_CONTACT_MOBILE = 'payment_link_contact_mobile_field',
  DEVICE_PAYMENT_LINK_CONTACT_EMAIL = 'payment_link_contact_email_field',
  DEVICE_PAYMENT_LINK_CONFIRMATION_FIELD = 'payment_link_confirmation_field',
  DEVICE_PAYMENT_LINK_STATUS_FIELD = 'payment_link_status_field',
  DEVICE_CHECK_PAYMENT_LINK_STATUS_FIELD = 'check_payment_link_status_field',
  DEVICE_PAYMENT_LINK_ID_FIELD = 'payment_link_id_field',
  DEVICE_PAYMENT_LINK_URL_FIELD = 'payment_link_url_field',
  DEVICE_GENERATE_PAYMENT_LINK_FIELD = 'generate_payment_link_field',
  DEVICE_RESEND_PAYMENT_LINK_FIELD = 'resend_payment_link_field',
  DEVICE_PAYMENT_LINK_CREATED_AT_FIELD = 'payment_link_created_at_field',
  DEVICE_PAYMENT_LINK_COMPLETED_AT_FIELD = 'payment_completed_at_field',
  DEVICE_CREATE_QR_CODE_FIELD = 'create_qr_code_field',
  DEVICE_QR_PAYMENT_STATUS_FIELD = 'qr_payment_status_field',
  DEVICE_QR_CODE_STATUS_FIELD = 'qr_code_status_field',
  DEVICE_CLOSE_QR_FIELD = 'close_qr_field',
}

export enum QuantityActions {
  add = 'add',
  reduce = 'reduce',
}

export type AvailableDevicePlans = 'monthly' | 'half_yearly' | 'quarterly' | 'yearly' | 'lifetime';
export type DeviceExtraFeatures = MODULAR_DEVICE_FIELDS.DEVICE_ADVANCE_RENTAL_FEE;

export interface DeviceFee {
  title: string | ((selectedPlan: string) => string);
  field: MODULAR_DEVICE_FIELDS.DEVICE_SETUP_FEE_TYPE | MODULAR_DEVICE_FIELDS.DEVICE_RENTAL_TYPE;
  customAmountField:
    | MODULAR_DEVICE_FIELDS.DEVICE_SETUP_CUSTOM_FEE_AMOUNT
    | MODULAR_DEVICE_FIELDS.DEVICE_RENTAL_CUSTOM_AMOUNT;
  isHidden?: (args: PlanConfig) => boolean;
}

export interface DeviceOptionalFeature {
  title: string;
  field: MODULAR_DEVICE_FIELDS.DEVICE_ADVANCE_RENTAL_FEE;
  customInputField?: MODULAR_DEVICE_FIELDS.DEVICE_ADVANCE_RENTAL_PERIOD_FIELD;
}

export interface AddDeviceToCartForm {
  [MODULAR_DEVICE_FIELDS.DEVICE_NAME]: string;
  [MODULAR_DEVICE_FIELDS.DEVICE_QUANTITY]: number;
  [MODULAR_DEVICE_FIELDS.DEVICE_PLAN]: AvailableDevicePlans;
  [MODULAR_DEVICE_FIELDS.DEVICE_SETUP_FEE_TYPE]: string;
  [MODULAR_DEVICE_FIELDS.DEVICE_SETUP_CUSTOM_FEE_AMOUNT]: string;
  [MODULAR_DEVICE_FIELDS.DEVICE_RENTAL_TYPE]: string;
  [MODULAR_DEVICE_FIELDS.DEVICE_RENTAL_CUSTOM_AMOUNT]: string;
  [MODULAR_DEVICE_FIELDS.DEVICE_ADVANCE_RENTAL_PERIOD_FIELD]: string;
  [MODULAR_DEVICE_FIELDS.DEVICE_ADVANCE_RENTAL_FEE]: boolean;
}

export interface DevicePlanCharge {
  name: string;
  key: 'setupFee' | 'rentalCharge' | 'oneTimeCharge';
}

export interface DeviceFeeOptions {
  name: string;
  key: 'standard' | 'custom';
}

export type OrderSummaryItemWithDeviceConfig = DeviceOrderSummaryItem & {
  deviceConfig: DeviceConfig | null;
};

export interface EditDeviceInCartForm extends AddDeviceToCartForm {
  [MODULAR_DEVICE_FIELDS.DEVICE_ID]: string;
}

export enum DeliveryAddressFields {
  line1 = 'line1',
  line2 = 'line2',
  city = 'city',
  zipcode = 'zipcode',
  state = 'state',
  country = 'country',
  landmark = 'landmark',
  name = 'name',
  contact = 'contact',
}

export interface DeviceDeliveryAddress {
  [DeliveryAddressFields.line1]: string;
  [DeliveryAddressFields.line2]: string;
  [DeliveryAddressFields.city]: string;
  [DeliveryAddressFields.zipcode]: string;
  [DeliveryAddressFields.state]: string;
  [DeliveryAddressFields.country]: string;
  [DeliveryAddressFields.landmark]: string;
  [DeliveryAddressFields.name]: string;
  [DeliveryAddressFields.contact]: string;
}

export type DeviceDeliveryAddressTypes = 'registered' | 'operation';
export type DeliveryAddresses = Record<DeviceDeliveryAddressTypes, DeviceDeliveryAddress>;
export type DevicePaymentStatus =
  | 'pending'
  | 'success'
  | 'payment_completed'
  | 'payment_pending'
  | 'expired';

export enum MILESTONE_NAME_FIELDS {
  SALES_MILESTONE = 'sales_milestone',
  PARTNER_MILESTONE = 'partner_milestone',
  PARTNER_DEVICE_DEPLOYMENT_MILESTONE = 'partner_device_deployment_milestone',
}

export enum PaymentStatusEnum {
  pending = 'pending',
  expired = 'expired',
  paid = 'paid',
}

export type PaymentLinkStatusType = 'created' | 'expired' | 'paid' | 'cancelled' | '';
export type QrPaymentStatusType = 'success' | 'pending' | 'expired' | '';
