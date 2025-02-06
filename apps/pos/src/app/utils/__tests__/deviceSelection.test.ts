import { Merchant } from '@dashboard/shared-utils/graphql/graph-types';
import { QuantityActions } from '../../types/DeviceSelection';
import {
  getCatalogDataFromModularConfig,
  getDeviceChargesFromModularConfig,
  getDevicePaymentFields,
  getDeviceStepStatus,
  getFieldsForDeliveryAddressFromMerchantDetails,
  getOrderSummaryFieldsFromModularConfig,
  updateQuantity,
} from '../deviceSelection';
import { MerchantModularOnboardingDetailsSuccessResponse } from '../../types/modular';
import { SUCCESS_MODULAR_RESPONSE } from 'apps/pos/src/services/mocks/fixtures/modularConfig';
import { MOCK_MERCHANT_DETAILS } from 'apps/pos/src/services/mocks/fixtures/merchantDetails';
import { TestAddedDeviceWithDeviceConfig } from 'apps/pos/src/services/mocks/fixtures/deviceSelection';

describe('deviceSelection utils', () => {
  const modularResponse = SUCCESS_MODULAR_RESPONSE.merchantModularOnboardingDetailsAsSales;
  const modularConfig =
    modularResponse as unknown as MerchantModularOnboardingDetailsSuccessResponse;
  describe('updateQuantity', () => {
    test('should return correct quantity on increase', () => {
      const quantity = updateQuantity({ currentQuantity: 1, action: 'add' as QuantityActions });
      expect(quantity).toBe(2);
    });

    test('should return correct quantity on reduce', () => {
      const quantity = updateQuantity({ currentQuantity: 2, action: 'reduce' as QuantityActions });
      expect(quantity).toBe(1);
    });

    test('should return correct quantity if action invalid', () => {
      const quantity = updateQuantity({ currentQuantity: 2, action: 'invalid' as QuantityActions });
      expect(quantity).toBe(2);
    });
  });

  describe('getCatalogDataFromModularConfig', () => {
    const modularResponse = SUCCESS_MODULAR_RESPONSE.merchantModularOnboardingDetailsAsSales;
    const modularConfig =
      modularResponse as unknown as MerchantModularOnboardingDetailsSuccessResponse;
    test('should return correct catalog for device when modular config passed', () => {
      const { addedDevices, deviceConfig } = getCatalogDataFromModularConfig({
        modularConfig,
      });
      expect(addedDevices).toEqual([
        expect.objectContaining({
          deviceName: 'Test Device',
        }),
      ]);

      expect(deviceConfig?.[0]).toEqual(
        expect.objectContaining({
          title: 'Test Device',
        }),
      );
    });
  });

  describe('getOrderSummaryFieldsFromModularConfig', () => {
    test('should return added device with deviceConfig', () => {
      const orderSummaryFields = getOrderSummaryFieldsFromModularConfig({
        modularConfig,
      });
      expect(orderSummaryFields?.addedDevices[0]).toEqual(
        expect.objectContaining({
          deviceName: 'Test Device',
          deviceConfig: expect.objectContaining({
            title: 'Test Device',
            defaultValues: {
              device_item_advanced_rental_field: false,
              device_item_plan_field: 'monthly',
              device_item_quantity_field: 1,
              device_item_rental_charges_type_field: 'standard',
              device_item_setup_fee_type_field: 'standard',
            },
          }),
        }),
      );
    });

    test('should return order summary', () => {
      const orderSummaryFields = getOrderSummaryFieldsFromModularConfig({
        modularConfig,
      });
      expect(orderSummaryFields?.orderSummary).toEqual(
        expect.objectContaining({
          advanceRentalCharge: 0,
          deviceCharge: 2000,
          gst: 360,
          orderId: '',
          paperRollCharge: 0,
          rentalCharge: [
            {
              deviceName: 'Test Device',
              fee: 100,
              gst: 18,
              renewal: 'monthly',
            },
          ],
          shippingCharge: 0,
          totalOrderCharge: 2478,
          totalRentalCharge: 118,
        }),
      );
    });

    test('should return documents if uploaded', () => {
      const orderSummaryFields = getOrderSummaryFieldsFromModularConfig({
        modularConfig,
      });
      expect(orderSummaryFields?.customPricingDocuments).toEqual([
        { fileStoreId: 'file_1234', name: 'test.pdf', size: 3000 },
      ]);
    });
  });

  describe('getFieldsForDeliveryAddressFromMerchantDetails', () => {
    test('should return delivery address from merchant details', () => {
      const { registered, operation } = getFieldsForDeliveryAddressFromMerchantDetails({
        merchant: MOCK_MERCHANT_DETAILS as unknown as Merchant,
        countryCode: 'IN',
      });

      expect(registered).toEqual({
        name: 'Test Merchant',
        contact: '1234567890',
        line1: '32, 1st ave',
        line2: '420',
        city: 'Bengaluru',
        country: 'IN',
        state: 'Karnataka',
        zipcode: '560034',
        landmark: '',
      });

      expect(operation).toEqual({
        name: 'Test Merchant',
        contact: '1234567890',
        line1: '32, 1st ave',
        line2: '420',
        city: 'Bengaluru',
        country: 'IN',
        state: 'Karnataka',
        zipcode: '560034',
        landmark: '',
      });
    });
  });

  describe('getDeviceChargesFromModularConfig', () => {
    test('should return device charges from modular config', () => {
      const orderSummary = getDeviceChargesFromModularConfig({
        orderSummaryItem: TestAddedDeviceWithDeviceConfig,
      });

      expect(orderSummary).toEqual({
        device_item_advanced_rental_periods_field: '1000',
        device_item_advanced_rental_field: true,
        device_item_custom_rental_charges_field: '200',
        device_item_custom_setup_fee_field: '13000',
        device_item_id_field: 'test-device',
        device_item_name_field: 'Test Device',
        device_item_plan_field: 'monthly',
        device_item_quantity_field: 1,
        device_item_rental_charges_type_field: 'standard',
        device_item_setup_fee_type_field: 'standard',
      });
    });
  });

  describe('getDevicePaymentFields', () => {
    test('should return correct device payment fields', () => {
      const { qrImageContent, qrPaymentStatus, qrTotalAmount } = getDevicePaymentFields({
        modularConfig,
        isPosEkycAgent: true,
        isBackendPLExptOn: false,
      });

      expect(qrImageContent).toBe(
        'upi://pay?ver=01&mode=22&pa=9876543210@okicici&pn=OMNIE2ETEST&tr=RZPOc9SJqyfJU1gtAqrv2&cu=INR&mc=5193&qrMedium=04&tn=PaymenttoOMNIE2ETEST&am=2478.00',
      );
      expect(qrPaymentStatus).toBe('pending');
      expect(qrTotalAmount).toBe('2478');
    });
  });

  describe('getDeviceStepStatus', () => {
    test('should return correct step status', () => {
      const stepStatus = getDeviceStepStatus({
        modularConfig,
      });
      expect(stepStatus).toBe('payment_pending');
    });
  });
});
