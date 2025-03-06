import moment from 'moment';
import { AMOUNT_ATTRIBUTES, NUMBER_TYPES, TIME_PRESETS } from './constants';

export function normalizeEvents(events) {
  const normalizeAttributes = (config, prefix = '') => {
    let result = {};

    for (const key in config) {
      if (config[key].type === 'map' && config[key].map) {
        // Recursively flatten map attributes without including "map" in the keys
        Object.assign(result, normalizeAttributes(config[key].map, `${prefix}${key}.`));
      } else if (config[key].type) {
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

export const combineEpochAndTime = (epochTimestamp, timeString) => {
  const epochInSeconds = Math.floor(epochTimestamp / 1000);

  const date = moment.unix(epochInSeconds).utc();

  const [hours, minutes] = timeString.split(':').map(Number);

  date.set({ hour: hours, minute: minutes, second: 0 });

  return date.unix();
};

export const generateRuleString = (rules) => {
  return rules
    .map((rule) => {
      const { field, operator, minValue, maxValue, value, type } = rule;
      const eventField = `Event.${field}`; // Prefix field with "Event."

      // Multiply amount values by 100 to convert to paise
      const finalValue = AMOUNT_ATTRIBUTES.includes(field) ? value * 100 : value;
      const finalMinValue =
        AMOUNT_ATTRIBUTES.includes(field) && minValue ? minValue * 100 : minValue;
      const finalMaxValue =
        AMOUNT_ATTRIBUTES.includes(field) && maxValue ? maxValue * 100 : maxValue;

      // Convert backend types to JavaScript types
      const isNumberType = NUMBER_TYPES.includes(type);
      const isStringType = type === 'string';
      const isBooleanType = type === 'boolean';

      // Function to properly escape string values
      const escapeString = (str) => `"${String(str).replace(/"/g, '\\"')}"`;

      switch (operator) {
        // String & Number: Equal To
        case 'equal_to':
          return isStringType
            ? `${eventField} == ${escapeString(finalValue)}`
            : `${eventField} == ${finalValue}`;

        // String: Contains
        case 'contains':
          return `${eventField} in [${escapeString(finalValue)}]`;

        // Number-based conditions
        case 'greater_than':
          return isNumberType ? `${eventField} > ${finalValue}` : '';
        case 'greater_than_or_equal':
          return isNumberType ? `${eventField} >= ${finalValue}` : '';
        case 'less_than':
          return isNumberType ? `${eventField} < ${finalValue}` : '';
        case 'less_than_or_equal':
          return isNumberType ? `${eventField} <= ${finalValue}` : '';
        case 'is_between':
          return isNumberType
            ? `${eventField} >= ${finalMinValue} && ${eventField} <= ${finalMaxValue}`
            : '';

        // Boolean: is
        case 'is':
          return isBooleanType ? `${eventField} == ${finalValue === 'yes' ? 'true' : 'false'}` : '';

        default:
          return ''; // Handle unknown operators gracefully
      }
    })
    .filter(Boolean)
    .join(' && '); // Remove empty rules and join with "&&"
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
      budget: campaignLimitAmount * 100,
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
      budget: userLimitAmount * 100,
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
