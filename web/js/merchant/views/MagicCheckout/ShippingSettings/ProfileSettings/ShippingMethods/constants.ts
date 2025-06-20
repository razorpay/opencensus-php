import DeliveryDescription from './Inputs/DeliveryDescription';
import DeliveryIn from './Inputs/DeliveryIn';
import DeliveryName from './Inputs/DeliveryName';
import DeliveryType from './Inputs/DeliveryType';
import Rate from './Inputs/Rate';
import ShippingSlab from './Inputs/ShippingSlab';
// import SubscribedRate from './Inputs/SubscribedRate';
import { ShippingFeeRule } from './types';

export const StandardDeliveryInputs = [
  [DeliveryType, DeliveryName, DeliveryDescription],
  [Rate, ShippingSlab],
  // [SubscribedRate],
  [DeliveryIn],
];

const maxValues = {
  hours: 24 * 7, // 1 week
  days: 365, // 1 year
  'working days': 261, // 1 year (approx. 5 working days per week)
  weeks: 52, // 1 year
};

export const getDefaultFormValues = () =>
  Object.assign(
    {},
    {
      name: {
        value: '',
        error: '',
        validation: (val: string): string =>
          val.length > 3 && val.length < 35 ? '' : 'Invalid input',
      },
      description: {
        value: '',
        error: '',
        validation: (val: string): string =>
          val.length > 3 && val.length < 35 ? '' : 'Invalid input',
      },
      etd: {
        value: '',
        error: '',
        validation: (val: string): string =>
          val.length >= 0 && val.length < 15 ? '' : 'Invalid input',
      },
      estimated_delivery_details: {
        value: {
          display: false,
          min_timeframe: '',
          max_timeframe: '',
          unit: 'days',
        },
        error: '',
        validation: (val: any): string => {
          const { min_timeframe, max_timeframe, display } = val;
          if (!display) return '';
          // if max_timeframe is present, min_timeframe should be present
          if (min_timeframe === '') return 'Please enter minimum shipping timeframe.';
          if (max_timeframe === '') return 'Please enter maximum shipping timeframe.';

          // min_timeframe should be greater than or equal to 0 and max_timeframe should be greater than 0 and max_timeframe should be greater than min_timeframe and unit should be present
          if (min_timeframe >= 0 && max_timeframe < min_timeframe) {
            return 'Maximum timeframe must be greater than or equal to the minimum timeframe.';
          }

          if (val.unit && max_timeframe > maxValues[val.unit]) {
            return `Maximum timeframe for ${val.unit} is ${maxValues[val.unit]}.`;
          }

          return '';
        },
      },
      delivery_type: {
        value: 'standard_delivery',
        error: '',
        validation: (val: string): string => (val.length > 0 ? '' : 'Invalid input'),
      },
      fee: {
        value: 100,
        error: '',
        validation: (val: number): string => (val >= 0 ? '' : 'Invalid input'),
      },
      allow_cod: {
        value: true,
        error: '',
        validation: (_val: boolean): string => '',
      },

      fee_rules: {
        value: {
          amount: {
            lt: 1000,
            gte: 0,
          },
        },
        error: '',
        validation: (val: ShippingFeeRule): string => {
          const { amount, weight } = val;
          let isValid = true;
          if (amount) {
            isValid = isValid && amount.gte >= 0 && amount.lt > 0 && amount.lt > amount.gte;
          }
          if (weight) {
            isValid = isValid && weight.gte >= 0 && weight.lt > 0 && weight.lt > weight.gte;
          }
          return isValid ? '' : 'Invalid input';
        },
      },
      attribute_rules: {
        value: {
          customer_tags: {},
        },
        error: '',
        validation: (val: any): string => {
          const { customer_tags } = val;
          for (const [key, value] of Object.entries(customer_tags)) {
            if (!key || (value as number) < 0) return 'Invalid input';
          }

          return '';
        },
      },
    },
  );
