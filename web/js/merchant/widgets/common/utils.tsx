import { ArrowRightIcon, IconComponent } from '@razorpay/blade/components';
import { CurrencyCodeType, convertToMajorUnit } from '@razorpay/i18nify-js';
import cloneDeep from 'lodash/cloneDeep';
import moment from 'moment';

import { currencySymbols } from 'common/utils/rzp-utils';
import { BASE_ROUTES } from 'merchant/components/Sidebar';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import {
  commonWidgetKeyToComponentMapping,
  inputKeyToComponentMapping,
} from 'merchant/widgets/common/mapping';
import { renderWidgetProps } from 'merchant/widgets/types';
import { renderWidget } from 'merchant/widgets/utils';

import { DateRangeValues } from './Select/types';
import { ChartDataType, ChartSchemaType, PointType, Dataset, durationOptionKeys } from './types';

export const getActionWidgetIcon = (type: string): IconComponent | undefined => {
  if (type === 'arrow_right') {
    return ArrowRightIcon;
  }
  return undefined;
};

export const convertToNumber = (value: string): number => {
  const number = Number(value);
  return isNaN(number) ? 0 : number;
};

const getParamsString = (params: Record<string, any> = {}) => {
  return new URLSearchParams(params).toString();
};

export const makeLink = (key: string, params: Record<string, any> = {}) => {
  const areParamsAvailable = params.from && params.to;

  switch (key.toLowerCase()) {
    case 'additional_website':
    case 'submit_website':
    case 'resubmit_website':
    case 'add_additional_website':
      return ROUTES_INFO.BUSINESS_WEBSITE_SETTINGS;
    case 'generate_api_keys':
      return ROUTES_INFO.API_KEYS;
    case 'increase_international_transaction_limit':
    case 'international':
    case 'toggle_international_revamped':
      return ROUTES_INFO.INTERNATIONAL_PAYMENTS;
    case 'increase_transaction_limit':
      return ROUTES_INFO.TRANSACTION_LIMITS;
    case 'gstin_update_self_serve':
      return ROUTES_INFO.GST_DETAILS;
    case 'bank_detail_update':
      return ROUTES_INFO.BANK_ACCOUNT_DETAILS;
    case 'support_ticket':
      return ROUTES_INFO.SUPPORT_TICKETS_MERCHANT;
    case 'payment_details':
      return params.payment_id
        ? `/payments/${params.payment_id}`
        : areParamsAvailable
        ? `/payments?${getParamsString(params)}`
        : '/payments';
    case 'refund_details':
      return params.refund_id
        ? `/refunds/${params.refund_id}`
        : areParamsAvailable
        ? `/refund?${getParamsString(params)}`
        : '/refunds';
    case 'payment_failed':
      return `/failed-payments${areParamsAvailable ? `?${getParamsString(params)}` : ''}`;
    case `refund_failed`:
      return `/refunds?${getParamsString(params)}`;
    case `qr_codes`:
      return BASE_ROUTES.qrCodes;
    case 'payment_links':
      return BASE_ROUTES.paymentlinks;
    default:
      return null;
  }
};

export const getCommonWidget = ({
  widget,
  isLoading = false,
  queryKey = [],
  analyticsProperties = {},
}: renderWidgetProps) =>
  renderWidget({
    widget,
    isLoading,
    queryKey,
    widgetMapping: commonWidgetKeyToComponentMapping,
    analyticsProperties,
  });

export const renderInput = ({
  widget,
  value,
  onChange,
  analyticsProperties = {},
  customRange,
  variables,
}: any) => {
  const widgetComponent = inputKeyToComponentMapping[widget.type];
  if (widgetComponent) {
    return widgetComponent({
      ...widget,
      value,
      variables,
      onChange,
      analyticsProperties,
      customRange: widget.name === 'date' && widget.type === 'select' ? customRange : undefined,
    });
  } else {
    return null;
  }
};

export function formatXAxis(
  point: PointType['x'],
  schema: ChartSchemaType['x'],
  unit?: DateRangeValues,
  nextPossiblePoint?: PointType['x'], // for custom range and last month preset only
  isCustomRange = false,
) {
  const { type } = schema;
  if (type === 'timestamp') {
    if (unit === durationOptionKeys.LAST_30_DAYS) {
      // MomentJS week is ending on Saturday, we want a week to run from Monday-Sunday
      const startDate = moment(Number(point) * 1000);
      let endDate = isCustomRange ? moment() : moment().subtract(1, 'day');

      const endOfWeekTimestamp = nextPossiblePoint
        ? moment.unix(Number(nextPossiblePoint))
        : endDate;
      endDate = endOfWeekTimestamp > endDate ? endDate : endOfWeekTimestamp;

      return `${startDate.format(getTimestampFormat(unit))} - ${endDate.format(
        getTimestampFormat(unit),
      )}`;
    }
    return moment(Number(point) * 1000).format(getTimestampFormat(unit));
  }
  if (type === 'string') return point;
  return point;
}

export function formatYAxis(point: PointType['y'], schema: ChartSchemaType['y']) {
  const pointNumber = convertToNumber(point);
  switch (schema.type) {
    case 'amount': {
      const currency = (schema.unit || 'INR') as CurrencyCodeType;
      return convertToMajorUnit(pointNumber, { currency });
    }
    case 'number':
      return pointNumber;
    default:
      return pointNumber;
  }
}

export const getChartData = (
  _chartData: ChartDataType,
  unit?: DateRangeValues,
): {
  labels: Array<string>;
  datasets: Array<Dataset>;
} => {
  /**
   * creates a deep copy of the chartdata object
   * this is required to avoid issues with in-place modifications (using pop below)
   * that can cause problems on subsequent renders
   */
  const chartData = cloneDeep(_chartData);
  let isCustomRange = false;
  let lastDateInRange: PointType['x'];

  if (
    chartData.type === 'line' &&
    (unit === durationOptionKeys.CUSTOM || unit === durationOptionKeys.LAST_30_DAYS)
  ) {
    const pointset = chartData.data[0].points;
    const fromDate = moment.unix(+pointset[0].x);
    const lastDate = moment.unix(+pointset[pointset.length - 1].x);

    if (unit === durationOptionKeys.LAST_30_DAYS) {
      /**
       * last point is used as a end point marker for custom range and last month preset
       * hence removing it from every dataset
       */
      chartData.data.forEach((dataSet) => dataSet.points.pop());
      lastDateInRange = `${lastDate.unix()}`;
    } else if (unit === durationOptionKeys.CUSTOM) {
      isCustomRange = true;

      if (fromDate.isSame(lastDate, 'day')) {
        unit = durationOptionKeys.TODAY;
      } else if (lastDate.diff(fromDate, 'days') < 14) {
        unit = durationOptionKeys.LAST_7_DAYS;
      } else {
        unit = durationOptionKeys.LAST_30_DAYS;
        chartData.data.forEach((dataSet) => dataSet.points.pop());
        lastDateInRange = `${lastDate.unix()}`;
      }
    }
  }

  const labels = chartData.data[0].points.map((point, index, points) => {
    if (unit === durationOptionKeys.LAST_30_DAYS && chartData.type === 'line') {
      const nextPossiblePoint = points[index + 1]
        ? moment(+points[index + 1].x * 1000) // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
            .subtract(1, 'day')
            .unix()
            .toString()
        : lastDateInRange;
      return formatXAxis(point.x, chartData.schema.x, unit, nextPossiblePoint, isCustomRange);
    }
    return formatXAxis(point.x, chartData.schema.x, unit);
  });

  const datasets: Array<Dataset> = chartData.data.map((dataSet) => ({
    label: dataSet.label,
    data: dataSet.points.map((point) => formatYAxis(point.y, chartData.schema.y)),
    fill: false,
    schema: chartData.schema,
    currency_symbol:
      chartData.schema.y.type === 'amount' ? currencySymbols[chartData.schema.y.unit] : undefined,
  }));

  return {
    labels,
    datasets,
  };
};

export const TOOLTIP_CHART_CONFIG = {
  tooltips: {
    enabled: true,
    position: 'nearest',
    callbacks: {
      label: (tooltipItem, data) => {
        const dataset = data.datasets[tooltipItem.datasetIndex];
        const schemaY = dataset.schema.y;
        const value = data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index];
        if (schemaY.type === 'amount') {
          const currencySymbol = dataset.currency_symbol || '₹';
          return `${dataset.label}: ${currencySymbol}${value}`;
        }
        return `${dataset.label}: ${value}`;
      },
    },
  },
};

function getTimestampFormat(unit?: DateRangeValues) {
  switch (unit) {
    case durationOptionKeys.TODAY:
      return 'hh:mm A';
    case durationOptionKeys.LAST_7_DAYS:
      return 'MMM DD';
    case durationOptionKeys.LAST_30_DAYS:
      return 'MMM DD';
    default:
      return 'MMM DD';
  }
}

export const getWidgetStyles = (
  ...args: Array<Record<string, any> | undefined>
): Record<string, any> => {
  return args.reduce((parent, child) => {
    return { ...parent, ...(child ?? {}) };
  }, {}) as Record<string, any>;
};
