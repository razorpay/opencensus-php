import DeliveryDescription from './Inputs/DeliveryDescription';
import DeliveryIn from './Inputs/DeliveryIn';
import DeliveryName from './Inputs/DeliveryName';
import DeliveryType from './Inputs/DeliveryType';
import Rate from './Inputs/Rate';
import ShippingSlab from './Inputs/ShippingSlab';
import SubscribedRate from './Inputs/SubscribedRate';
import { ShippingFeeRule } from './types';

export const StandardDeliveryInputs = [
  [DeliveryType, DeliveryName, DeliveryDescription],
  [Rate, ShippingSlab],
  [SubscribedRate],
  [DeliveryIn],
];

export const getDefaultFormValues = () =>
  Object.assign(
    {},
    {
      name: {
        value: '',
        error: '',
        validation: (val: string): boolean => val.length > 3 && val.length < 30,
      },
      description: {
        value: '',
        error: '',
        validation: (val: string): boolean => val.length > 3 && val.length < 30,
      },
      etd: {
        value: '',
        error: '',
        validation: (val: string): boolean => val.length >= 0 && val.length < 15,
      },
      delivery_type: {
        value: 'standard_delivery',
        error: '',
        validation: (val: string): boolean => val.length > 0,
      },
      fee: {
        value: 100,
        error: '',
        validation: (val: number): boolean => val >= 0,
      },
      allow_cod: {
        value: true,
        error: '',
        validation: (_val: boolean): boolean => true,
      },

      fee_rules: {
        value: {
          amount: {
            lt: 1000,
            gte: 0,
          },
        },
        error: '',
        validation: (val: ShippingFeeRule): boolean => {
          const { amount, weight } = val;
          let isValid = true;
          if (amount) {
            isValid = isValid && amount.gte >= 0 && amount.lt > 0 && amount.lt > amount.gte;
          }
          if (weight) {
            isValid = isValid && weight.gte >= 0 && weight.lt > 0 && weight.lt > weight.gte;
          }
          return isValid;
        },
      },
      attribute_rules: {
        value: {
          customer_tags: {},
        },
        error: '',
        validation: (val: any): boolean => {
          const { customer_tags } = val;
          for (const [key, value] of Object.entries(customer_tags)) {
            if (!key || (value as number) < 0) return false;
          }
          return true;
        },
      },
    },
  );
