import {
  getComponentFromStep,
  getFieldFromComponent,
  getProgressFromModularStep,
  getStepsFromModularConfig,
  processFormDataForModularSubmit,
} from '../modularConfig';
import { MerchantModularOnboardingDetailsSuccessResponse } from '../../types/modular';
import { SUCCESS_MODULAR_RESPONSE } from 'apps/pos/src/services/mocks/fixtures/modularConfig';

describe('modularconfig utils', () => {
  const modularResponse = SUCCESS_MODULAR_RESPONSE.merchantModularOnboardingDetailsAsSales;
  const modularConfig =
    modularResponse as unknown as MerchantModularOnboardingDetailsSuccessResponse;

  describe('getStepsFromModularConfig', () => {
    test('should return step from modular config ', () => {
      const step = getStepsFromModularConfig({
        modularConfig,
      });

      expect(step[0].name).toBe('device_selection_step');
      expect(step[1].name).toBe('pricing_step');
    });
  });

  describe('getComponentFromStep', () => {
    test('should return component from modular config ', () => {
      const component = getComponentFromStep({
        modularConfig,
        component: 'device_catalogue_component',
        step: 'device_selection_step',
      });

      expect(component?.name).toBe('device_catalogue_component');
    });

    test('should return null if component not found', () => {
      const component = getComponentFromStep({
        modularConfig,
        component: 'some_random_component',
        step: 'device_selection_step',
      });

      expect(component).toBe(null);
    });
  });

  describe('getFieldFromComponent', () => {
    test('should return field from component', () => {
      const field = getFieldFromComponent({
        modularConfig,
        component: 'device_catalogue_component',
        fieldName: 'device_order_items_summary_field',
        step: 'device_selection_step',
      });
      expect(field).toStrictEqual(
        expect.objectContaining({
          name: 'device_order_items_summary_field',
        }),
      );
    });

    test('should return null if field not found', () => {
      const field = getFieldFromComponent({
        modularConfig,
        component: 'device_catalogue_component',
        fieldName: 'some_random_field',
        step: 'device_selection_step',
      });
      expect(field).toBe(null);
    });
  });

  describe('processFormDataForModularSubmit', () => {
    test('should remove entries with undefined values', () => {
      const input = {
        name: 'Test Merchant',
        age: undefined,
        email: 'test.merchant@example.com',
      };
      const expectedOutput = {
        name: 'Test Merchant',
        email: 'test.merchant@example.com',
      };
      expect(processFormDataForModularSubmit(input)).toEqual(expectedOutput);
    });

    test('should convert string numbers to actual numbers', () => {
      const input = {
        age: '25',
        height: '175.5',
        name: 'Test Merchant',
      };
      const expectedOutput = {
        age: 25,
        height: 175.5,
        name: 'Test Merchant',
      };
      expect(processFormDataForModularSubmit(input)).toEqual(expectedOutput);
    });
  });

  describe('getProgressFromModularStep', () => {
    test('should return progress from modular step', () => {
      const progress = getProgressFromModularStep({
        modularConfig,
        step: 'device_selection_step',
      });
      expect(progress).toBe('pending');
    });

    test('should return completed as progress from modular step', () => {
      const progress = getProgressFromModularStep({
        modularConfig,
        step: 'pricing_step',
      });
      expect(progress).toBe('completed');
    });
  });
});
