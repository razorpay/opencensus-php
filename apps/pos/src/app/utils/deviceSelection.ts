import { MerchantDetails } from '../types/SalesAssistedOnboarding';
import {
  isArrayOfDocumentsUpload,
  isBooleanValue,
  isDeviceCharges,
  isOrderSummaryItem,
  isStringValue,
} from './modularTypeResolvers';

import {
  DeviceConfig,
  MerchantModularOnboardingDetailsSuccessResponse,
  DeviceOrderSummaryItem,
  DeviceCharges,
  ArrayOfDocumentFieldsUpload,
  ModularOnboardingOption,
} from 'apps/pos/src/app/types/modular';
import {
  AvailableDevicePlans,
  DeliveryAddresses,
  DeviceDeliveryAddress,
  DeviceDeliveryAddressTypes,
  DevicePaymentStatus,
  EditDeviceInCartForm,
  MODULAR_DEVICE_FIELDS,
  OrderSummaryItemWithDeviceConfig,
  PaymentLinkStatusType,
  QrPaymentStatusType,
  QuantityActions,
} from 'apps/pos/src/app/types/DeviceSelection';
import {
  getComponentFromStep,
  getFieldFromComponent,
  getProgressFromModularStep,
} from 'apps/pos/src/app/utils/modularConfig';

interface UpdateQuantityProps {
  currentQuantity: number;
  action: QuantityActions;
}

export const updateQuantity = ({ currentQuantity, action }: UpdateQuantityProps): number => {
  switch (action) {
    case QuantityActions.add:
      return currentQuantity + 1;
    case QuantityActions.reduce:
      return currentQuantity - 1;
    default:
      return currentQuantity;
  }
};

interface GetDeviceCatalogFromModularConfigProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse;
}

interface GetDeviceCatalogFromModularConfig {
  deviceConfig: DeviceConfig[] | null;
  addedDevices: DeviceOrderSummaryItem[];
}

export enum DeviceModel {
  SOUNDBOX_KIT = 'Soundbox Kit',
  STICKER_AND_STANDEE = 'Sticker and Standee',
}

export const getCatalogDataFromModularConfig = ({
  modularConfig,
}: GetDeviceCatalogFromModularConfigProps): GetDeviceCatalogFromModularConfig => {
  const component = getComponentFromStep({
    modularConfig,
    step: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
    component: MODULAR_DEVICE_FIELDS.DEVICE_CATALOG_COMPONENT,
  });

  const modularField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
    component: MODULAR_DEVICE_FIELDS.DEVICE_CATALOG_COMPONENT,
    fieldName: MODULAR_DEVICE_FIELDS.DEVICE_ORDER_ITEMS_SUMMARY_FIELD,
  });

  const { addedDevices } =
    modularField && isOrderSummaryItem(modularField) ? modularField : { addedDevices: [] };
  return {
    deviceConfig: (component?.meta?.deviceConfig as DeviceConfig[]) ?? null,
    addedDevices,
  };
};

interface GetOrderSummaryFieldsFromModularConfigProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
}

interface GetOrderSummaryFieldsFromModularConfig {
  orderSummary: DeviceCharges;
  addedDevices: OrderSummaryItemWithDeviceConfig[];
  customPricingDocuments: ArrayOfDocumentFieldsUpload[];
  isCustomRatesApplicable: boolean;
}

export const getOrderSummaryFieldsFromModularConfig = ({
  modularConfig,
}: GetOrderSummaryFieldsFromModularConfigProps): GetOrderSummaryFieldsFromModularConfig | null => {
  if (!modularConfig) return null;

  const addedDevicesField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
    component: MODULAR_DEVICE_FIELDS.DEVICE_CART_COMPONENT,
    fieldName: MODULAR_DEVICE_FIELDS.DEVICE_ORDER_ITEMS_SUMMARY_FIELD,
  });

  const deviceConfig = getCatalogDataFromModularConfig({ modularConfig })?.deviceConfig;

  const orderSummaryField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
    component: MODULAR_DEVICE_FIELDS.DEVICE_CART_COMPONENT,
    fieldName: MODULAR_DEVICE_FIELDS.DEVICE_ORDER_SUMMARY_FIELD,
  });

  const arrayOfDocumentsUploadValueField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
    component: MODULAR_DEVICE_FIELDS.DEVICE_CART_COMPONENT,
    fieldName: MODULAR_DEVICE_FIELDS.DEVICE_CUSTOM_PRICING_DOCS,
  });

  const deviceCustomRatesApplicableField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
    component: MODULAR_DEVICE_FIELDS.DEVICE_CART_COMPONENT,
    fieldName: MODULAR_DEVICE_FIELDS.DEVICE_CUSTOM_RATES_APPLICABLE,
  });

  const addedDevices = isOrderSummaryItem(addedDevicesField) ? addedDevicesField.addedDevices : [];
  const addedDeviceWithDeviceConfig = addedDevices.map((device) => {
    const deviceConfigItem = deviceConfig?.find((config) => config?.title === device.deviceName);
    return {
      ...device,
      deviceConfig: deviceConfigItem ?? null,
    };
  });

  return {
    orderSummary: isDeviceCharges(orderSummaryField) ? orderSummaryField.orderSummary : {},
    addedDevices: addedDeviceWithDeviceConfig,
    customPricingDocuments: isArrayOfDocumentsUpload(arrayOfDocumentsUploadValueField)
      ? arrayOfDocumentsUploadValueField.arrayOfDocumentsUploadValue
      : [],
    isCustomRatesApplicable: isBooleanValue(deviceCustomRatesApplicableField)
      ? deviceCustomRatesApplicableField.booleanValue
      : false,
  };
};

interface GetDeviceChargesFromModularConfigProps {
  orderSummaryItem: OrderSummaryItemWithDeviceConfig;
}
export const getDeviceChargesFromModularConfig = ({
  orderSummaryItem,
}: GetDeviceChargesFromModularConfigProps): EditDeviceInCartForm => {
  return {
    [MODULAR_DEVICE_FIELDS.DEVICE_NAME]: orderSummaryItem?.deviceName,
    [MODULAR_DEVICE_FIELDS.DEVICE_ID]: orderSummaryItem?.itemId,
    [MODULAR_DEVICE_FIELDS.DEVICE_PLAN]: orderSummaryItem?.renewal as AvailableDevicePlans,
    [MODULAR_DEVICE_FIELDS.DEVICE_QUANTITY]: orderSummaryItem?.quantity as number,
    [MODULAR_DEVICE_FIELDS.DEVICE_SETUP_FEE_TYPE]: orderSummaryItem?.setupChargeType ?? '',
    [MODULAR_DEVICE_FIELDS.DEVICE_SETUP_CUSTOM_FEE_AMOUNT]: String(
      orderSummaryItem?.setupCharge ?? '',
    ),
    [MODULAR_DEVICE_FIELDS.DEVICE_ADVANCE_RENTAL_PERIOD_FIELD]: String(
      orderSummaryItem?.totalAdvanceRentalCharge ?? '',
    ),
    [MODULAR_DEVICE_FIELDS.DEVICE_RENTAL_TYPE]: orderSummaryItem?.rentalChargeType ?? '',
    [MODULAR_DEVICE_FIELDS.DEVICE_RENTAL_CUSTOM_AMOUNT]: String(
      orderSummaryItem?.rentalCharge ?? '',
    ),
    [MODULAR_DEVICE_FIELDS.DEVICE_ADVANCE_RENTAL_FEE]:
      (orderSummaryItem?.totalAdvanceRentalCharge || 0) > 0,
  };
};

interface GetFieldsForDeliveryAddressFromMerchantDetailsProps {
  merchant: MerchantDetails;
  countryCode: string;
}

export const getFieldsForDeliveryAddressFromMerchantDetails = ({
  merchant,
  countryCode,
}: GetFieldsForDeliveryAddressFromMerchantDetailsProps): DeliveryAddresses => {
  const { contactPerson, business } = merchant;
  const { address } = business;

  const getAddressFields = (type: DeviceDeliveryAddressTypes): DeviceDeliveryAddress => ({
    name: contactPerson?.name.value ?? '',
    contact: contactPerson?.phone.value.number ?? '',
    city: address[type]?.city?.value ?? '',
    country: countryCode,
    line1: address[type]?.line1?.value ?? '',
    line2: address[type]?.line2?.value ?? '',
    state: address[type]?.state?.value ?? '',
    zipcode: address[type]?.zipCode?.value ?? '',
    landmark: '',
  });

  return {
    operation: getAddressFields('operation'),
    registered: getAddressFields('registered'),
  };
};

interface GetDevicePaymentFieldsProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse;
  isPosEkycAgent?: boolean;
  isBackendPLExptOn?: boolean;
}

interface GetDevicePaymentFields {
  qrImageContent: string;
  qrTotalAmount: string;
  qrPaymentStatus: DevicePaymentStatus;
}

export const getDevicePaymentFields = ({
  modularConfig,
  isPosEkycAgent,
  isBackendPLExptOn,
}: GetDevicePaymentFieldsProps): GetDevicePaymentFields => {
  const getQrComponentName = () => {
    if (isPosEkycAgent || !isBackendPLExptOn) {
      return MODULAR_DEVICE_FIELDS.DEVICE_QR_CODE_COMPONENT;
    }
    return MODULAR_DEVICE_FIELDS.DEVICE_QR_CODE_COMPONENT_V2;
  };

  const qrImageContentField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
    component: getQrComponentName(),
    fieldName: MODULAR_DEVICE_FIELDS.DEVICE_QR_IMAGE_CONTENT_FIELD,
  });

  const qrTotalAmountField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
    component: getQrComponentName(),
    fieldName: MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_AMOUNT_FIELD,
  });

  const isDeviceStepCompleted =
    getProgressFromModularStep({
      modularConfig,
      step: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
    }) === 'completed';

  return {
    qrImageContent: isStringValue(qrImageContentField) ? qrImageContentField.stringValue : '',
    qrTotalAmount: isStringValue(qrTotalAmountField) ? qrTotalAmountField.stringValue : '',
    qrPaymentStatus: isDeviceStepCompleted ? 'success' : 'pending',
  };
};

interface GetDeviceStepStatusProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
}

export const getDeviceStepStatus = ({
  modularConfig,
}: GetDeviceStepStatusProps): DevicePaymentStatus => {
  if (!modularConfig) return 'pending';
  const orderSummaryFields = getOrderSummaryFieldsFromModularConfig({ modularConfig });
  const { addedDevices = [] } = orderSummaryFields ?? {};
  const isDeviceStepCompleted =
    getProgressFromModularStep({
      modularConfig,
      step: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
    }) === 'completed';

  if (addedDevices?.length > 0 && !isDeviceStepCompleted) return 'payment_pending';
  else if (addedDevices?.length > 0 && isDeviceStepCompleted) return 'payment_completed';
  else return 'pending';
};

interface GetDevicePaymentMethodsProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse;
}
export const getDevicePaymentMethods = ({
  modularConfig,
}: GetDevicePaymentMethodsProps): ModularOnboardingOption[] | undefined => {
  const paymentOptionsField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
    component: MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_OPTIONS_COMPONENT,
    fieldName: MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_OPTIONS_FIELD,
  });
  return paymentOptionsField?.meta?.options;
};
interface GetDevicePaymentDetailsProps {
  modularConfig: MerchantModularOnboardingDetailsSuccessResponse | null;
}

interface GetPaymentLinkDetailsReturnType {
  paymentLinkStatus: PaymentLinkStatusType;
  qrCodePaymentStatus: QrPaymentStatusType;
  paymentLinkUrl: string;
  paymentLinkCreatedAt: string;
  paymentLinkCompletedAt: string;
}
export const getDevicePaymentDetails = ({
  modularConfig,
}: GetDevicePaymentDetailsProps): GetPaymentLinkDetailsReturnType | null => {
  if (!modularConfig) return null;
  const paymentLinkStatusField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
    component: MODULAR_DEVICE_FIELDS.DEVICE_SALES_ASSISTED_PAYMENT_LINK_COMPONENT,
    fieldName: MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_LINK_STATUS_FIELD,
  });
  const paymentLinkUrlField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
    component: MODULAR_DEVICE_FIELDS.DEVICE_SALES_ASSISTED_PAYMENT_LINK_COMPONENT,
    fieldName: MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_LINK_URL_FIELD,
  });
  const paymentLinkCreatedAt = getFieldFromComponent({
    modularConfig,
    step: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
    component: MODULAR_DEVICE_FIELDS.DEVICE_SALES_ASSISTED_PAYMENT_LINK_COMPONENT,
    fieldName: MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_LINK_CREATED_AT_FIELD,
  });
  const paymentLinkCompletedAt = getFieldFromComponent({
    modularConfig,
    step: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
    component: MODULAR_DEVICE_FIELDS.DEVICE_SALES_ASSISTED_PAYMENT_LINK_COMPONENT,
    fieldName: MODULAR_DEVICE_FIELDS.DEVICE_PAYMENT_LINK_COMPLETED_AT_FIELD,
  });
  const qrCodeStatusField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
    component: MODULAR_DEVICE_FIELDS.DEVICE_QR_CODE_COMPONENT_V2,
    fieldName: MODULAR_DEVICE_FIELDS.DEVICE_QR_CODE_STATUS_FIELD,
  });
  const qrPaymentStatusField = getFieldFromComponent({
    modularConfig,
    step: MODULAR_DEVICE_FIELDS.DEVICE_SELECTION_STEP,
    component: MODULAR_DEVICE_FIELDS.DEVICE_QR_CODE_COMPONENT_V2,
    fieldName: MODULAR_DEVICE_FIELDS.DEVICE_QR_PAYMENT_STATUS_FIELD,
  });
  let qrPaymentStatus: QrPaymentStatusType = '';
  // qrCodeStatusField can be '', 'active', 'closed'
  if (isStringValue(qrCodeStatusField) && qrCodeStatusField.stringValue === 'active') {
    qrPaymentStatus = 'pending';
  }
  if (isStringValue(qrCodeStatusField) && qrCodeStatusField.stringValue === 'closed') {
    if (isStringValue(qrPaymentStatusField) && qrPaymentStatusField.stringValue === 'success') {
      qrPaymentStatus = 'success';
    }
    if (isStringValue(qrPaymentStatusField) && qrPaymentStatusField.stringValue === 'pending') {
      qrPaymentStatus = 'expired';
    }
  }
  return {
    paymentLinkStatus: isStringValue(paymentLinkStatusField)
      ? (paymentLinkStatusField.stringValue as PaymentLinkStatusType)
      : '',
    paymentLinkUrl: isStringValue(paymentLinkUrlField) ? paymentLinkUrlField.stringValue : '',
    paymentLinkCreatedAt: isStringValue(paymentLinkCreatedAt)
      ? paymentLinkCreatedAt.stringValue
      : '',
    paymentLinkCompletedAt: isStringValue(paymentLinkCompletedAt)
      ? paymentLinkCompletedAt.stringValue
      : '',
    qrCodePaymentStatus: qrPaymentStatus || '',
  };
};
