import {
  InsightCardRayProp,
  DateRangeOption,
  NonInsightCardStaticData,
  RayInsight,
  InsightCardType,
  OtherInsightCardType,
  Component,
} from './types';
import { predefinedMappings, staticContent } from './constants';
import { formatNumber } from '@razorpay/i18nify-js/currency';
import { paiseToRupees } from '@libs/shared-utils';
import moment from 'moment';
import { ButtonProps } from '@razorpay/blade/components';

export const getComponentByAlias = (components?: Component[], alias?: string) => {
  return components?.find((component) => component?.alias === alias);
};

// TODO: Move to common utils file as same used in business-summary
export const getTimeAgo = (timestamp: number | string) => {
  const time = typeof timestamp === 'string' ? parseInt(timestamp, 10) : timestamp;
  const now = moment();
  const givenTime = moment(time * 1000);

  const diffInSeconds = now.diff(givenTime, 'seconds');
  const diffInMinutes = now.diff(givenTime, 'minutes');
  const diffInHours = now.diff(givenTime, 'hours');
  const diffInDays = now.diff(givenTime, 'days');
  const diffInWeeks = now.diff(givenTime, 'weeks');
  const diffInMonths = now.diff(givenTime, 'months');
  const diffInYears = now.diff(givenTime, 'years');

  const formatUnit = (count: number, unit: string) => {
    const label = count === 1 ? unit : `${unit}s`;
    return `Updated ${count} ${label} ago`;
  };

  if (diffInSeconds <= 0) {
    return 'Updated now';
  } else if (diffInSeconds < 60) {
    return formatUnit(diffInSeconds, 'second');
  } else if (diffInMinutes < 60) {
    return formatUnit(diffInMinutes, 'minute');
  } else if (diffInHours < 24) {
    return formatUnit(diffInHours, 'hour');
  } else if (diffInDays < 7) {
    return formatUnit(diffInDays, 'day');
  } else if (diffInWeeks < 4) {
    return formatUnit(diffInWeeks, 'week');
  } else if (diffInMonths < 12) {
    return formatUnit(diffInMonths, 'month');
  } else {
    return formatUnit(diffInYears, 'year');
  }
};

export const getDateRange = (option: DateRangeOption): string => {
  const format = 'D MMM'; // Example: 1 Nov, 28 Sept

  switch (option) {
    case 'yesterday': {
      return moment().subtract(1, 'day').format('D MMM');
    }
    case 'last_7_days': {
      const start = moment().subtract(7, 'days').format(format);
      const end = moment().format(format);
      return `${start} - ${end}`;
    }
    case 'last_30_days': {
      const start = moment().subtract(30, 'days').format(format);
      const end = moment().subtract(1, 'day').format(format);
      return `${start} - ${end}`;
    }
    default:
      return 'Invalid date range';
  }
};

export const getPercentageChangeSign = (percentage_change: number): string => {
  return percentage_change > 0 ? 'positive' : percentage_change < 0 ? 'negative' : 'neutral';
};

function normalizePaymentMethod(method: string) {
  // If not found in predefined mappings, convert snake_case to Title Case
  return (
    predefinedMappings[method.toLowerCase()] ||
    method
      .toLowerCase()
      .split('_')
      .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ')
  );
}

export const getAmountSuffix = (range: string) => {
  switch (range) {
    case 'last_7_days':
      return 'vs last week';
    case 'last_30_days':
      return 'vs last month';
    case 'yesterday':
      return 'vs previous day';
    default:
      return 'Invalid Range';
  }
};

export const generateRayInsightContent = ({
  cardType,
  insightsStaticData,
  rayInsightsData,
  inputTime,
}: InsightCardRayProp) => {
  const { data_summary } = rayInsightsData;

  if (!data_summary) {
    return '';
  }
  const { input_time = inputTime, current_data, previous_data, percentage_change } = data_summary;

  const getTimePeriod = (selectedTime?: string, cardType?: string) => {
    switch (selectedTime) {
      case 'yesterday':
        return 'yesterday';
      case 'last_7_days':
        return cardType === 'refund' ? 'in the last 7 days' : 'this week';
      case 'last_30_days':
        return cardType === 'refund' ? 'in the last 30 days' : 'this month';
      default:
        return '';
    }
  };

  const formatCurrencyINR = (amount: number) => {
    amount = Number(paiseToRupees(amount)) || 0;
    return formatNumber(amount, {
      currency: 'INR',
      intlOptions: {
        currencyDisplay: 'narrowSymbol',
        notation: 'compact',
        maximumFractionDigits: 2,
      },
      locale: 'en-IN',
    });
  };

  const timePeriod = getTimePeriod(input_time, cardType);
  const currentValue = Number(current_data);
  const previousValue = Number(previous_data);

  switch (cardType) {
    case 'payment': {
      const { current_top_payment_method = '', previous_top_payment_method = '' } = rayInsightsData;
      const trend = currentValue >= previousValue ? 'surged' : 'reduced';
      const direction = currentValue >= previousValue ? 'up' : 'down';
      if (current_top_payment_method !== previous_top_payment_method) {
        return `${normalizePaymentMethod(
          current_top_payment_method,
        )} overtook ${normalizePaymentMethod(
          previous_top_payment_method,
        )} as the top payment method. Its usage ${trend} to ${current_data}% ${timePeriod}, ${direction} from ${previous_data}%.`;
      }
      return `${normalizePaymentMethod(
        current_top_payment_method,
      )} remains the top payment method. Its usage ${trend} to ${current_data}% ${timePeriod}, ${direction} from ${previous_data}%.`;
    }

    case 'success_rate': {
      const { payment_failure_error_code, payment_failure_error_description } = rayInsightsData;
      return `Among known error reasons, ${payment_failure_error_code} with the description "${payment_failure_error_description}" caused the most payment failures ${timePeriod}, accounting for ${percentage_change}% failures, with a total of ${current_data} failed payments.`;
    }

    case 'refund': {
      return `Your refunds accounted for ${Math.abs(
        percentage_change ?? 0,
      )}% of loss in gross revenue ${timePeriod}.`;
    }

    case 'payout': {
      const { business_name } = rayInsightsData;
      return `${normalizePaymentMethod(
        business_name ?? 'Default',
      )} was your biggest expense ${timePeriod}, amounting to ${formatCurrencyINR(
        Number(current_data) ?? 0,
      )}.`;
    }

    default:
      return '';
  }
};

export const generateNonInsightComponentData = (
  { title, description, path }: NonInsightCardStaticData,
  alias: string,
) => {
  return {
    id: '351',
    type: 'other_insight_item',
    title: title,
    description: description,
    actions: [
      {
        title: staticContent.getStartedText,
        action: 'navigate',
        type: 'button',
        icon: '',
        icon_position: '',
        action_params: {
          path: path,
        },
        properties: {
          variant: 'secondary' as ButtonProps['variant'],
        },
      },
    ],
    inputs: [],
    components: [],
    alias: alias,
    analytics: null,
    styles: null,
  };
};

export const isEmptyRayInsight = (cardType: string, ray_insight?: RayInsight) => {
  if (!ray_insight || Object.keys(ray_insight).length === 0) return true;

  const mandatoryFields = {
    payment: [
      'data_summary.current_data',
      'data_summary.previous_data',
      'current_top_payment_method',
    ],
    success_rate: [
      'data_summary.current_data',
      'data_summary.percentage_change',
      'payment_failure_error_code',
      'payment_failure_error_description',
    ],
    refund: ['data_summary.percentage_change'],
    payout: ['data_summary.current_data', 'business_name'],
  };

  const fields = mandatoryFields[cardType as InsightCardType | OtherInsightCardType];
  if (!fields) return true; // Invalid type

  return fields.some((field) => {
    const keys = field.split('.'); // Split nested path (e.g., 'data_summary.current_data')
    let value: any = ray_insight;

    // Traverse nested keys
    for (const key of keys) {
      value = value?.[key];

      if (value === null || value === undefined || value === false || value === '') return true;
    }
    return false;
  });
};

export const getAbsolutePath = (path?: string) => {
  if (!path) return '/'; // Handle empty paths
  return path.startsWith('/') ? path : `/${path}`;
};
