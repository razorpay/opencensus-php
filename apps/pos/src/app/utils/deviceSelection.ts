import { MerchantDetails } from '../types/SalesAssistedOnboarding';
import {
  isArrayOfDocumentsUpload,
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
} from 'apps/pos/src/app/types/modular';
import {
  AvailableDevicePlans,
  DeliveryAddresses,
  DeviceDeliveryAddress,
  DeviceDeliveryAddressTypes,
  DevicePaymentStatus,
  EditDeviceInCartForm,
  OrderSummaryItemWithDeviceConfig,
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

export const getCatalogDataFromModularConfig = ({
  modularConfig,
}: GetDeviceCatalogFromModularConfigProps): GetDeviceCatalogFromModularConfig => {
  const component = getComponentFromStep({
    modularConfig,
    step: 'device_selection_step',
    component: 'device_catalogue_component',
  });

  const modularField = getFieldFromComponent({
    modularConfig,
    step: 'device_selection_step',
    component: 'device_catalogue_component',
    fieldName: 'device_order_items_summary_field',
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
}

export const getOrderSummaryFieldsFromModularConfig = ({
  modularConfig,
}: GetOrderSummaryFieldsFromModularConfigProps): GetOrderSummaryFieldsFromModularConfig | null => {
  if (!modularConfig) return null;

  const addedDevicesField = getFieldFromComponent({
    modularConfig,
    step: 'device_selection_step',
    component: 'device_cart_component',
    fieldName: 'device_order_items_summary_field',
  });

  const deviceConfig = getCatalogDataFromModularConfig({ modularConfig })?.deviceConfig;

  const orderSummaryField = getFieldFromComponent({
    modularConfig,
    step: 'device_selection_step',
    component: 'device_cart_component',
    fieldName: 'device_order_summary_field',
  });

  const arrayOfDocumentsUploadValueField = getFieldFromComponent({
    modularConfig,
    step: 'device_selection_step',
    component: 'device_cart_component',
    fieldName: 'device_custom_pricing_documents_field',
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
  };
};

interface GetDeviceChargesFromModularConfigProps {
  orderSummaryItem: OrderSummaryItemWithDeviceConfig;
}
export const getDeviceChargesFromModularConfig = ({
  orderSummaryItem,
}: GetDeviceChargesFromModularConfigProps): EditDeviceInCartForm => {
  return {
    device_item_name_field: orderSummaryItem?.deviceName,
    device_item_id_field: orderSummaryItem?.itemId,
    device_item_plan_field: orderSummaryItem?.renewal as AvailableDevicePlans,
    device_item_quantity_field: orderSummaryItem?.quantity as number,
    device_item_setup_fee_type_field: orderSummaryItem?.setupChargeType ?? '',
    device_item_custom_setup_fee_field: String(orderSummaryItem?.setupCharge ?? ''),
    device_item_advanced_rental_periods_field: String(
      orderSummaryItem?.totalAdvanceRentalCharge ?? '',
    ),
    device_item_rental_charges_type_field: orderSummaryItem?.rentalChargeType ?? '',
    device_item_custom_rental_charges_field: String(orderSummaryItem?.rentalCharge ?? ''),
    device_item_purchase_paper_rolls_quantity_field: String(
      orderSummaryItem?.paperRollQuantity ?? '',
    ),
    device_item_advanced_rental_field: (orderSummaryItem?.totalAdvanceRentalCharge || 0) > 0,
    device_item_purchase_paper_rolls_field: (orderSummaryItem?.paperRollQuantity || 0) > 0,
  };
};

interface GetFieldsForDeliveryAddressFromMerchantDetailsProps {
  merchant: MerchantDetails;
}

export const getFieldsForDeliveryAddressFromMerchantDetails = ({
  merchant,
}: GetFieldsForDeliveryAddressFromMerchantDetailsProps): DeliveryAddresses => {
  const { contactPerson, business } = merchant;
  const { address } = business;

  const getAddressFields = (type: DeviceDeliveryAddressTypes): DeviceDeliveryAddress => ({
    name: contactPerson?.name.value ?? '',
    contact: contactPerson?.phone.value.number ?? '',
    city: address[type]?.city?.value ?? '',
    country: address[type]?.country?.value ?? '',
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
}

interface GetDevicePaymentFields {
  qrImageContent: string;
  qrTotalAmount: string;
  qrPaymentStatus: DevicePaymentStatus;
}

export const getDevicePaymentFields = ({
  modularConfig,
}: GetDevicePaymentFieldsProps): GetDevicePaymentFields => {
  const qrImageContentField = getFieldFromComponent({
    modularConfig,
    step: 'device_selection_step',
    component: 'qrcode_component',
    fieldName: 'qr_image_content_field',
  });

  const qrTotalAmountField = getFieldFromComponent({
    modularConfig,
    step: 'device_selection_step',
    component: 'qrcode_component',
    fieldName: 'qr_payment_amount_field',
  });

  const qrPaymentStatusField = getFieldFromComponent({
    modularConfig,
    step: 'device_selection_step',
    component: 'qrcode_component',
    fieldName: 'qr_payment_status_field',
  });

  return {
    qrImageContent: isStringValue(qrImageContentField) ? qrImageContentField.stringValue : '',
    qrTotalAmount: isStringValue(qrTotalAmountField) ? qrTotalAmountField.stringValue : '',
    qrPaymentStatus: isStringValue(qrPaymentStatusField)
      ? (qrPaymentStatusField.stringValue as DevicePaymentStatus)
      : 'pending',
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
    getProgressFromModularStep({ modularConfig, step: 'device_selection_step' }) === 'completed';

  if (addedDevices?.length > 0 && !isDeviceStepCompleted) return 'payment_pending';
  else if (addedDevices?.length > 0 && isDeviceStepCompleted) return 'payment_completed';
  else return 'pending';
};
