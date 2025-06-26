import moment from 'moment';
import {
  AMOUNT_ATTRIBUTES,
  COMPARISON_OPERATORS,
  CONSTANT_ACTION_CONFIGS,
  NUMBER_TYPES,
  TIME_PRESETS,
} from './constants';
import { rupeesToPaise } from '@libs/shared-utils';

export function normalizeEvents(events) {
  const normalizeAttributes = (config, prefix = '', isInsideArray = false) => {
    let result = {};

    for (const key in config) {
      if (config[key].type === 'array') {
        // Handle array type
        const arrayConfig = config[key].array || {};

        // If we're already inside an array and encounter another array, skip it entirely
        if (isInsideArray) {
          // Skip nested arrays completely
          continue;
        }

        // If array contains maps, normalize their structure without including the parent array
        if (arrayConfig.type === 'map' && arrayConfig.map) {
          // Normalize map structure within array (skip adding the parent array itself)
          // Pass isInsideArray=true to indicate we're inside an array now
          Object.assign(result, normalizeAttributes(arrayConfig.map, `${prefix}${key}[].`, true));
        } else {
          // For scalar arrays, include the array entry with element type information and add [] suffix
          result[`${prefix}${key}[]`] = {
            type: 'array',
            required: config[key].required,
            // Store the element type for proper formatting
            elementType: arrayConfig.type || 'string',
          };
        }
      } else if (config[key].type === 'map' && config[key].map) {
        // Recursively flatten map attributes without including "map" in the keys
        // Maintain the isInsideArray parameter
        Object.assign(
          result,
          normalizeAttributes(config[key].map, `${prefix}${key}.`, isInsideArray),
        );
      } else if (config[key].type) {
        // This is a scalar value
        result[`${prefix}${key}`] = {
          type: config[key].type,
          required: config[key].required,
        };
      }
    }

    return result;
  };

  let normalizedOutput = {};

  events.forEach((event) => {
    normalizedOutput[event.id] = {
      attributes: normalizeAttributes(event.payload_config),
      name: event.name,
    };
  });

  return normalizedOutput;
}

// Get available start time options
export const getAvailableStartTimes = (startDate) => {
  if (!startDate) {
    return TIME_PRESETS;
  }

  const today = new Date();

  const isToday = startDate.toDateString() === today.toDateString();

  if (!isToday) {
    return TIME_PRESETS;
  }

  const currentHours = today.getHours();
  const currentMinutes = today.getMinutes();

  return TIME_PRESETS.filter(({ value }) => {
    const [hours, minutes] = value.split(':').map(Number);
    return hours > currentHours || (hours === currentHours && minutes > currentMinutes);
  });
};

// Get available end time options based on selected start time
export const getAvailableEndTimes = (startTime, isSameDay) => {
  if (!startTime || !isSameDay) return getAvailableStartTimes(null);

  return TIME_PRESETS.filter(({ value }) => {
    return moment(value, 'HH:mm').isAfter(moment(startTime, 'HH:mm'));
  });
};

export const combineEpochAndTime = (dateObj, timeString) => {
  const [hours, minutes] = timeString.split(':').map(Number);

  const combinedTimestamp = moment(dateObj).set({
    hour: hours,
    minute: minutes,
    second: 0,
    millisecond: 0,
  });

  return combinedTimestamp.utc().unix();
};

//Sanitize values for attributes which requires special formatting
export const sanitizeRuleValues = (field, value) => {
  switch (field) {
    case 'program_id':
      if (value.startsWith('iprog_')) {
        return value; // Return as it is if the prefix is already present
      } else {
        return `iprog_${value}`; // Prefix with 'iprog_' if not present
      }
    default:
      return value;
  }
};

export const removeArrayNotation = (str: string): string => {
  if (!str) return '';
  return str.replace(/\[\]/g, '');
};

export const isAmountField = (field: string): boolean => {
  if (!field) return false;
  return AMOUNT_ATTRIBUTES.some((amountSuffix) => field.endsWith(amountSuffix));
};

export const generateRuleString = (rules) => {
  const ruleStrings = rules
    .map((rule) => {
      const { field, operator, minValue, maxValue, value, type } = rule;

      // Skip fields that have [] in the middle (array elements), but process fields with [] at the end (scalar arrays)
      if (field.includes('[].')) {
        return '';
      }

      // Remove [] suffix if it exists (for scalar arrays)
      const cleanField = field.endsWith('[]') ? field.slice(0, -2) : field;
      const eventField = `Event.${cleanField}`; // Prefix field with "Event."

      // Function to properly escape string values for non-array contexts
      const escapeString = (str) => `"${String(str).replace(/"/g, '\\"')}"`;

      let finalValue = value;

      // Handle scalar arrays and string field with contains operator
      if (
        operator === COMPARISON_OPERATORS.CONTAINS_ALL ||
        operator === COMPARISON_OPERATORS.CONTAINS_ANY ||
        operator === COMPARISON_OPERATORS.CONTAINS
      ) {
        finalValue = sanitizeRuleValues(field, value);

        // Check if we have an array with an element type that is a number type
        const isNumberArray =
          type === 'array' && rule.elementType && NUMBER_TYPES.includes(rule.elementType);

        // Format the array value based on element type
        if (isNumberArray) {
          // For number arrays, don't quote the values: [1,2,3,4,5]
          // Split by comma and join back
          const numberElements = finalValue.split(',').map((item) => item.trim());
          let formattedNumberElements = numberElements;
          if (isAmountField(field)) {
            formattedNumberElements = numberElements.map((item) => rupeesToPaise(item));
          }
          return `${eventField} ${operator} [${formattedNumberElements.join(',')}]`;
        } else {
          // For string arrays: [\"1\",\"2\",\"3\"]
          const items = finalValue.split(',');
          const escapedItems = items.map((item) => escapeString(item.trim()));
          if (operator === COMPARISON_OPERATORS.CONTAINS) {
            return `${eventField} in [${escapedItems.join(',')}]`;
          } else {
            return `${eventField} ${operator} [${escapedItems.join(',')}]`;
          }
        }
      } else {
        finalValue = isAmountField(field) ? rupeesToPaise(value) : sanitizeRuleValues(field, value);
      }
      const finalMinValue = isAmountField(field) && minValue ? rupeesToPaise(minValue) : minValue;
      const finalMaxValue = isAmountField(field) && maxValue ? rupeesToPaise(maxValue) : maxValue;

      // Convert backend types to JavaScript types
      const isNumberType = NUMBER_TYPES.includes(type);
      const isStringType = type === 'string';
      const isBooleanType = type === 'boolean';

      switch (operator) {
        // String & Number: Equal To
        case COMPARISON_OPERATORS.EQUAL_TO:
          return isStringType
            ? `${eventField} == ${escapeString(finalValue)}`
            : `${eventField} == ${finalValue}`;

        // Number-based conditions
        case COMPARISON_OPERATORS.GREATER_THAN:
          return isNumberType ? `${eventField} > ${finalValue}` : '';
        case COMPARISON_OPERATORS.GREATER_THAN_OR_EQUAL:
          return isNumberType ? `${eventField} >= ${finalValue}` : '';
        case COMPARISON_OPERATORS.LESS_THAN:
          return isNumberType ? `${eventField} < ${finalValue}` : '';
        case COMPARISON_OPERATORS.LESS_THAN_OR_EQUAL:
          return isNumberType ? `${eventField} <= ${finalValue}` : '';
        case COMPARISON_OPERATORS.IS_BETWEEN:
          return isNumberType
            ? `${eventField} >= ${finalMinValue} && ${eventField} <= ${finalMaxValue}`
            : '';

        // Boolean: is
        case COMPARISON_OPERATORS.IS:
          return isBooleanType ? `${eventField} == ${finalValue === 'yes' ? 'true' : 'false'}` : '';

        case COMPARISON_OPERATORS.CONTAINS_ALL:
          return `${eventField} contains_all [${escapeString(finalValue)}]`;

        case COMPARISON_OPERATORS.CONTAINS_ANY:
          return `${eventField} contains_any [${escapeString(finalValue)}]`;

        default:
          return ''; // Handle unknown operators gracefully
      }
    })
    .filter(Boolean)
    .join(' && ');

  return ruleStrings;
};

export const createUsageLimits = ({
  campaignLimitAmountEnabled,
  campaignLimitAmount,
  campaignLimitAmountPeriod,
  campaignLimitActionsEnabled,
  campaignLimitActions,
  campaignLimitActionsPeriod,
  userLimitAmountEnabled,
  userLimitAmount,
  userLimitAmountPeriod,
  userLimitActionsEnabled,
  userLimitActions,
  userLimitActionsPeriod,
}) => {
  const usage_limits: Array<{
    type: 'SINGLE_CAMPAIGN' | 'SINGLE_CAMPAIGN_USER';
    config: Array<{
      budget?: number;
      count?: number;
      frequency: string;
    }>;
  }> = [];

  // Process SINGLE_CAMPAIGN
  let campaignConfig: Array<{
    budget?: number;
    count?: number;
    frequency: string;
  }> = [];
  if (campaignLimitAmountEnabled) {
    campaignConfig.push({
      budget: rupeesToPaise(campaignLimitAmount),
      frequency: campaignLimitAmountPeriod,
    });
  }
  if (campaignLimitActionsEnabled) {
    const existingConfig = campaignConfig.find((c) => c.frequency === campaignLimitActionsPeriod);
    if (existingConfig) {
      existingConfig.count = campaignLimitActions;
    } else {
      campaignConfig.push({
        count: campaignLimitActions,
        frequency: campaignLimitActionsPeriod,
      });
    }
  }
  if (campaignConfig.length > 0) {
    usage_limits.push({
      type: 'SINGLE_CAMPAIGN',
      config: campaignConfig,
    });
  }

  // Process SINGLE_CAMPAIGN_USER
  let userConfig: Array<{
    budget?: number;
    count?: number;
    frequency: string;
  }> = [];

  if (userLimitAmountEnabled) {
    userConfig.push({
      budget: rupeesToPaise(userLimitAmount),
      frequency: userLimitAmountPeriod,
    });
  }
  if (userLimitActionsEnabled) {
    const existingConfig = userConfig.find((c) => c.frequency === userLimitActionsPeriod);
    if (existingConfig) {
      existingConfig.count = userLimitActions;
    } else {
      userConfig.push({
        count: userLimitActions,
        frequency: userLimitActionsPeriod,
      });
    }
  }
  if (userConfig.length > 0) {
    usage_limits.push({
      type: 'SINGLE_CAMPAIGN_USER',
      config: userConfig,
    });
  }

  return usage_limits.length > 0 ? { usage_limits } : {};
};

export const getFilterRules = (rules) => {
  // Group rules by array path (part before [])
  // Only include rules with []. pattern (array elements), not those ending with [] (scalar arrays)
  const arrayRules = rules.filter((rule) => rule.field.includes('[].'));
  const rulesByArrayPath = {};

  // Group rules by their array path
  arrayRules.forEach((rule) => {
    const arrayPath = rule.field.split('[].')[0];
    const fieldName = rule.field.split('[].')[1];

    if (!rulesByArrayPath[arrayPath]) {
      rulesByArrayPath[arrayPath] = [];
    }

    // Create a modified rule with the field part after []
    rulesByArrayPath[arrayPath].push({
      ...rule,
      field: fieldName,
    });
  });

  // Generate rule strings for each array path
  const result = {};

  Object.keys(rulesByArrayPath).forEach((arrayPath) => {
    const arrayRuleString = rulesByArrayPath[arrayPath]
      .map((rule) => {
        const { field, operator, minValue, maxValue, value, type } = rule;

        const eventField = `Event.${field}`;

        // escape string values
        const escapeString = (str) => `"${String(str).replace(/"/g, '\\"')}"`;

        const finalValue = isAmountField(field)
          ? rupeesToPaise(value)
          : sanitizeRuleValues(field, value);

        const finalMinValue = isAmountField(field) && minValue ? rupeesToPaise(minValue) : minValue;
        const finalMaxValue = isAmountField(field) && maxValue ? rupeesToPaise(maxValue) : maxValue;

        // Convert backend types to JavaScript types
        const isNumberType = NUMBER_TYPES.includes(type);
        const isStringType = type === 'string';
        const isBooleanType = type === 'boolean';

        switch (operator) {
          case COMPARISON_OPERATORS.EQUAL_TO:
            return isStringType
              ? `${eventField} == ${escapeString(finalValue)}`
              : `${eventField} == ${finalValue}`;
          case COMPARISON_OPERATORS.CONTAINS:
            const items = finalValue.split(',');
            const escapedItems = items.map((item) => escapeString(item.trim()));
            return `${eventField} in [${escapedItems.join(',')}]`;
          case COMPARISON_OPERATORS.GREATER_THAN:
            return isNumberType ? `${eventField} > ${finalValue}` : '';
          case COMPARISON_OPERATORS.GREATER_THAN_OR_EQUAL:
            return isNumberType ? `${eventField} >= ${finalValue}` : '';
          case COMPARISON_OPERATORS.LESS_THAN:
            return isNumberType ? `${eventField} < ${finalValue}` : '';
          case COMPARISON_OPERATORS.LESS_THAN_OR_EQUAL:
            return isNumberType ? `${eventField} <= ${finalValue}` : '';
          case COMPARISON_OPERATORS.IS_BETWEEN:
            return isNumberType
              ? `${eventField} >= ${finalMinValue} && ${eventField} <= ${finalMaxValue}`
              : '';
          case COMPARISON_OPERATORS.IS:
            return isBooleanType
              ? `${eventField} == ${finalValue === 'yes' ? 'true' : 'false'}`
              : '';
          default:
            return '';
        }
      })
      .filter(Boolean)
      .join(' && '); // Join with AND operator

    if (arrayRuleString) {
      result[arrayPath] = arrayRuleString;
    }
  });

  // Return null if there are no rules
  return Object.keys(result).length === 0 ? null : result;
};

export const getActionConfig = ({
  event,
  program_id,
  merchant_id,
}: {
  event: 'wallet_credit' | 'order_placed' | 'order_fulfilled';
  program_id: string;
  merchant_id: string;
}) => {
  const commonConfig = {
    program_id: {
      type: 'constant',
      value: program_id,
      ...(event === 'order_fulfilled' && { is_points_field: false }),
    },
    merchant_id: {
      type: 'constant',
      value: merchant_id,
      ...(event === 'order_fulfilled' && { is_points_field: false }),
    },
  };

  switch (event) {
    case 'wallet_credit':
      return {
        ...CONSTANT_ACTION_CONFIGS.CREDIT_WALLET,
        ...commonConfig,
      };
    case 'order_placed':
      return {
        ...CONSTANT_ACTION_CONFIGS.ORDER_PLACED,
        ...commonConfig,
      };
    case 'order_fulfilled':
      return {
        ...CONSTANT_ACTION_CONFIGS.ORDER_FULFILLED,
        ...commonConfig,
      };
    default:
      return {};
  }
};
