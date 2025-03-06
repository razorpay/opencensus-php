import * as yup from 'yup';

import { COMPARISON_OPERATORS, BOOLEAN_TYPES, NUMBER_TYPES } from './constants';
import { CreditTypeEnum } from './types';

// Yup validation schema
export const formSchema = yup.object().shape({
  triggerEvent: yup.string().required('Required'),

  triggerAttributes: yup
    .array()
    .of(
      yup.object().shape({
        field: yup.string().required('Required'),
        operator: yup.string().required('Required'),

        type: yup.string().notRequired(),

        value: yup.mixed().when('operator', {
          is: (operator) => operator === COMPARISON_OPERATORS.IS_BETWEEN,
          then: (schema) => schema.notRequired(),
          otherwise: (schema) =>
            schema
              .transform((value, originalValue) => {
                // If originalValue is a string and is empty after trimming, return undefined.
                if (typeof originalValue === 'string' && originalValue.trim() === '') {
                  return undefined;
                }
                return originalValue;
              })
              .required('Required'),
        }),

        minValue: yup
          .number()
          .transform((value, originalValue) =>
            originalValue === '' ? undefined : Number(originalValue),
          )
          .typeError('Must be a number')
          .when('operator', {
            is: COMPARISON_OPERATORS.IS_BETWEEN,
            then: (schema) => schema.required('Required'),
            otherwise: (schema) => schema.notRequired(),
          }),

        maxValue: yup
          .number()
          .transform((value, originalValue) =>
            originalValue === '' ? undefined : Number(originalValue),
          )
          .typeError('Must be a number')
          .when('operator', {
            is: COMPARISON_OPERATORS.IS_BETWEEN,
            then: (schema) =>
              schema
                .required('Required')
                .moreThan(yup.ref('minValue'), 'Maximum value must be greater than minimum value'),
            otherwise: (schema) => schema.notRequired(),
          }),
      }),
    )
    .nullable(),

  triggerAction: yup.string().required('Required'),

  selectedWallet: yup.string().required('Required'),

  creditType: yup.string().required('Required'),

  creditAmount: yup
    .number()
    .transform((value, originalValue) => (originalValue === '' ? undefined : value))
    .when('creditType', {
      is: CreditTypeEnum.FLAT,
      then: (schema) =>
        schema
          .typeError('Credit amount must be a number')
          .min(1, 'Credit amount must be greater than 0')
          .required('Required'),
      otherwise: (schema) =>
        schema
          .typeError('Credit amount must be a number')
          .min(0, 'Credit percentage must be at least 0')
          .max(100, 'Credit percentage must be less than or equal to 100')
          .required('Required'),
    }),

  triggerActionAttribute: yup.string().when('creditType', {
    is: (action) => action === CreditTypeEnum.PERCENTAGE,
    then: (schema) => schema.required('Required'),
    otherwise: (schema) => schema.notRequired(),
  }),

  maxCredit: yup
    .number()
    .transform((value, originalValue) => (originalValue === '' ? undefined : value))
    .when(['creditType', 'noMaxLimit'], {
      is: (creditType, noMaxLimit) => creditType === CreditTypeEnum.PERCENTAGE && !noMaxLimit,
      then: (schema) =>
        schema
          .typeError('Max credit must be a number')
          .min(1, 'Maximum credit amount must be greater than 0')
          .required('Required'),
      otherwise: (schema) => schema.notRequired(),
    }),

  noMaxLimit: yup.boolean(),

  expiryDuration: yup
    .number()
    .transform((value, originalValue) => (originalValue === '' ? undefined : value))
    .typeError('Must be a number')
    .required('Required')
    .min(1, 'Required')
    .when('expiryDurationPreset', {
      is: 'years',
      then: (schema) => schema.max(10, 'Expiry duration cannot be more than 10 years'),
    })
    .when('expiryDurationPreset', {
      is: 'months',
      then: (schema) => schema.max(120, 'Expiry duration cannot be more than 120 months'),
    })
    .when('expiryDurationPreset', {
      is: 'days',
      then: (schema) => schema.max(3650, 'Expiry duration cannot be more than 3650 days'),
    }),

  expiryDurationPreset: yup.string().required('Required'),

  minOrderValue: yup
    .number()
    .transform((value, originalValue) => (originalValue === '' ? undefined : value)) // Prevents empty strings from being valid
    .required('Required')
    .typeError('Minimum order value must be a number')
    .min(0, 'Minimum order value cannot be negative'),

  startDate: yup
    .date()
    .nullable()
    .when('startImmediately', {
      is: false,
      then: (schema) => schema.required('Required'),
      otherwise: (schema) => schema.nullable(),
    }),
  startTime: yup.string().when('startImmediately', {
    is: false,
    then: (schema) => schema.required('Required'),
    otherwise: (schema) => schema.nullable(),
  }),
  startImmediately: yup.boolean(),

  endDate: yup
    .date()
    .nullable()
    .when('noEndDate', {
      is: false,
      then: (schema) => schema.required('Required'),
      otherwise: (schema) => schema.nullable(),
    }),
  endTime: yup.string().when('noEndDate', {
    is: false,
    then: (schema) => schema.required('Required'),
    otherwise: (schema) => schema.nullable(),
  }),
  noEndDate: yup.boolean(),

  campaignLimitAmountEnabled: yup.boolean(),
  campaignLimitAmount: yup
    .number()
    .transform((value, originalValue) => (originalValue === '' ? undefined : value))
    .typeError('Must be a number')
    .when('campaignLimitAmountEnabled', {
      is: true,
      then: (schema) => schema.required('Required').min(1, 'Amount should be greater than 1'),
      otherwise: (schema) => schema.notRequired(),
    }),
  campaignLimitAmountPeriod: yup.string().when('campaignLimitAmountEnabled', {
    is: true,
    then: (schema) => schema.required('Required'),
    otherwise: (schema) => schema.notRequired(),
  }),

  campaignLimitActionsEnabled: yup.boolean(),
  campaignLimitActions: yup
    .number()
    .transform((value, originalValue) => (originalValue === '' ? undefined : value))
    .typeError('Must be a number')
    .when('campaignLimitActionsEnabled', {
      is: true,
      then: (schema) => schema.required('Required').min(1, 'Should be greater than 1'),
      otherwise: (schema) => schema.notRequired(),
    }),
  campaignLimitActionsPeriod: yup.string().when('campaignLimitActionsEnabled', {
    is: true,
    then: (schema) => schema.required('Required'),
    otherwise: (schema) => schema.notRequired(),
  }),

  userLimitAmountEnabled: yup.boolean(),
  userLimitAmount: yup
    .number()
    .transform((value, originalValue) => (originalValue === '' ? undefined : value))
    .typeError('Must be a number')
    .when('userLimitAmountEnabled', {
      is: true,
      then: (schema) => schema.required('Required').min(1, 'Amount should be greater than 1'),
      otherwise: (schema) => schema.notRequired(),
    }),
  userLimitAmountPeriod: yup.string().when('userLimitAmountEnabled', {
    is: true,
    then: (schema) => schema.required('Required'),
    otherwise: (schema) => schema.notRequired(),
  }),

  userLimitActionsEnabled: yup.boolean(),
  userLimitActions: yup
    .number()
    .transform((value, originalValue) => (originalValue === '' ? undefined : value))
    .typeError('Must be a number')
    .when('userLimitActionsEnabled', {
      is: true,
      then: (schema) => schema.required('Required').min(1, 'Should be greater than 1'),
      otherwise: (schema) => schema.notRequired(),
    }),
  userLimitActionsPeriod: yup.string().when('userLimitActionsEnabled', {
    is: true,
    then: (schema) => schema.required('Required'),
    otherwise: (schema) => schema.notRequired(),
  }),
});
