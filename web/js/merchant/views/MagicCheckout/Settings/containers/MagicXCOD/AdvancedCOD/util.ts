import { isValidPhoneNumber } from '@razorpay/i18nify-js/phoneNumber';

import { capitalize } from 'common/utils/rzp-utils';
import { isEmail } from 'common/utils/validators';

import type { Rule as APIRule } from 'merchant/reducers/magicCheckout/magicxACODRules/types';
import type {
  Condition,
  ConditionGroup,
  Rule,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const createNewRuleState = ({
  rulesCount,
  merchant_id,
  type,
}: {
  rulesCount: number;
  merchant_id: string;
  type: 'shipping' | 'payment';
}): Partial<APIRule> => ({
  merchant_id,
  type,
  name: `${capitalize(type)}Rule0${rulesCount + 1}`,
  description: '',
});

type RuleValidator = (rule: Rule) => {
  conditions: Partial<{
    [groupID: string]: Partial<{
      [conditionID: string]: {
        fact?: string;
        operator?: string;
        value?: string;
      };
    }>;
  }>;
  actions: Partial<{
    type: string;
    params: string;
  }>;
};
export const ruleValidator: RuleValidator = (rule) => {
  const rootCondition = rule.condition;
  const conditionGroups = rootCondition.conditions;
  const action = rule.actions[0];
  const validation: any = {
    conditions: {},
    actions: {},
  };

  // condition validation
  conditionGroups.forEach((conditionGroup: any) => {
    const groupErrors = {};
    const conditions = (conditionGroup as ConditionGroup).conditions as Array<
      NonNullable<Condition>
    >;

    conditions.forEach((condition: any) => {
      const conditionErrors: any = {};

      // generic validations
      if (condition.value === '') {
        conditionErrors.value = 'Value cannot be empty';
      }
      if (!Boolean(condition.fact)) {
        conditionErrors.fact = 'Please select a condition';
      }
      if (!Boolean(condition.operator)) {
        conditionErrors.operator = 'Please select an operator';
      }

      // specific case validations
      const fact = condition.fact.toLowerCase();
      if (!conditionErrors.value) {
        if (
          fact === 'phone' &&
          condition.value.split(',').some((phone) => !isValidPhoneNumber(phone.trim()))
        ) {
          conditionErrors.value = 'Enter Phone Number with Country Code e.g. +919997778886';
        }

        if (
          fact === 'customeremail' &&
          condition.value.split(',').some((email) => !isEmail(email.trim()))
        ) {
          conditionErrors.value = 'Enter a valid email address e.g. user@gmail.com';
        }

        if (
          fact === 'zipcode' &&
          condition.value.split(',').some((zipcode) => !Boolean(zipcode.trim()))
        ) {
          conditionErrors.value = 'Enter comma separated values e.g. 560021,400101';
        }

        const isNumericValueCondition =
          fact === 'orderamount' ||
          fact === 'quantity' ||
          fact === 'subtotal' ||
          fact === 'discountpercentage' ||
          fact === 'weight';
        if (isNumericValueCondition && Number.isNaN(parseFloat(condition.value))) {
          conditionErrors.value = 'Enter a non-negative number';
        }
      }

      if (Object.keys(conditionErrors).length > 0) {
        groupErrors[condition.id] = conditionErrors;
      }
    });

    if (Object.keys(groupErrors).length > 0) {
      validation.conditions[conditionGroup.id] = groupErrors;
    }
  });

  // action validation
  if (!Boolean(action.type)) {
    validation.actions.type = 'Select an action for the rule';
  } else {
    const paramsValue = action.params?.value;
    const ruleType = /shipping/.test(action.type.toLowerCase()) ? 'shipping' : 'payment';

    if (paramsValue && Array.isArray(paramsValue)) {
      if (paramsValue.length === 0) {
        validation.actions.params =
          ruleType === 'shipping' ? 'Select shipping methods' : 'Enter payment methods';
      } else if (paramsValue.some((val) => !Boolean(val)) && ruleType === 'payment') {
        validation.actions.params = 'Enter comma separated payment methods';
      }
    }
  }

  return validation;
};
