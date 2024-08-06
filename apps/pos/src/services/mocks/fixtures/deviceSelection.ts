import { OrderSummaryItemWithDeviceConfig } from 'apps/pos/src/app/types/DeviceSelection';
import {
  DeviceCharges,
  DeviceConfig,
  DeviceOrderSummaryItem,
} from 'apps/pos/src/app/types/modular';

export const TestDeviceConfig: DeviceConfig = {
  defaultValues: {},
  icon: 'somerandomicon',
  title: 'Test Device',
  rateConfig: [
    {
      active: true,
      advancedRentalMonths: 1220,
      paperRollCharges: 100,
      name: 'Test Plan',
      plans: [
        {
          planName: 'monthly',
          planDisplayName: 'Monthly',
          oneTimeCharge: 1000,
          rentalCharge: 200,
          setupFee: 13000,
        },
        {
          planName: 'lifetime',
          planDisplayName: 'Lifetime',
          oneTimeCharge: 1000,
          rentalCharge: 0,
          setupFee: 30000,
        },
      ],
    },
  ],
};

export const TestAddedDevice: DeviceOrderSummaryItem = {
  deviceName: 'Test Device',
  rentalCharge: 200,
  itemId: 'test-device',
  paperRollCharge: 100,
  paperRollQuantity: 1,
  quantity: 1,
  renewal: 'monthly',
  rentalChargeType: 'standard',
  setupCharge: 13000,
  setupChargeType: 'standard',
  totalAdvanceRentalCharge: 1000,
  totalPaperRollCharge: 1000,
  totalRentalCharge: 200,
  totalSetupCharge: 30000,
};

export const TestAddedDeviceWithDeviceConfig: OrderSummaryItemWithDeviceConfig = {
  ...TestAddedDevice,
  deviceConfig: TestDeviceConfig,
};

export const TestDeviceOrderSummary: DeviceCharges = {
  advanceRentalCharge: 0,
  deviceCharge: 2000,
  gst: 360,
  orderId: '',
  paperRollCharge: 0,
  shippingCharge: 0,
  totalOrderCharge: 2478,
  totalRentalCharge: 118,
  rentalCharge: [
    {
      deviceName: 'Test Device',
      fee: 100,
      gst: 18,
      renewal: 'monthly',
    },
  ],
};
