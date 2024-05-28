import { ArrowRightIcon, LinkProps } from '@razorpay/blade/components';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { renderWidgetProps } from 'merchant/widgets/types';
import {
  commonWidgetKeyToComponentMapping,
  inputKeyToComponentMapping,
} from 'merchant/widgets/common/mapping';
import { renderWidget } from 'merchant/widgets/utils';
import { ChartDataType, ChartSchemaType, PointType } from './types';
import { i18CurrencyConversionFromMinorUnitToCommonUnit } from 'common/utils/rzp-utils';
import moment from 'moment';
import { DateRangeValues } from './Select/types';
import { BASE_ROUTES } from 'merchant/components/Sidebar';

export const getLinkWidgetIcon = (type: string): LinkProps['icon'] => {
  if (type === 'arrow_right') {
    return ArrowRightIcon;
  }
  return undefined;
};

export const makeLink = (key: string, params: Record<string, any> = {}) => {
  switch (key.toLowerCase()) {
    case 'additional_website':
    case 'add_additional_website':
      return ROUTES_INFO.BUSINESS_WEBSITE_SETTINGS;
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
      return `/payments/${params.payment_id ?? ''}`;
    case 'refund_details':
      return `/refunds/${params.refund_id ?? ''}`;
    case 'payment_failed':
      return '/failed-payments';
    case `refund_failed`:
      return `/refunds?${new URLSearchParams(params).toString()}`;
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

export const renderInput = ({ widget, value, onChange, analyticsProperties = {} }: any) => {
  const widgetComponent = inputKeyToComponentMapping[widget.type];
  if (widgetComponent) {
    return widgetComponent({ ...widget, value, onChange, analyticsProperties });
  } else {
    return null;
  }
};

export function formatXAxis(
  point: PointType['x'],
  schema: ChartSchemaType['x'],
  unit?: DateRangeValues,
) {
  const { type } = schema;
  if (type === 'timestamp') {
    if (unit === 'last_30_days') {
      const startDate = moment(Number(point) * 1000);
      // MomentJS week is ending on Saturday, we want a week to run from Monday-Sunday
      const endOfWeekTimestamp = moment(Number(point) * 1000)
        .endOf('week')
        .add(1, 'day');
      const currentTimestamp = moment();
      const endDate = endOfWeekTimestamp > currentTimestamp ? currentTimestamp : endOfWeekTimestamp;
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
  switch (schema.type) {
    case 'amount':
      return i18CurrencyConversionFromMinorUnitToCommonUnit(point, schema.unit);
    case 'number':
      return point;
    default:
      return point;
  }
}

export const getChartData = (
  chartData: ChartDataType,
  unit?: DateRangeValues,
): {
  labels: Array<string>;
  datasets: Array<{
    label: string;
    data: Array<number>;
    fill: boolean;
    backgroundColor?: Array<string>;
    borderWidth?: number;
    borderColor?: string;
  }>;
} => {
  const labels = chartData.data[0].points.map((point) =>
    formatXAxis(point.x, chartData.schema.x, unit),
  );
  const datasets = chartData.data.map((dataSet) => ({
    label: dataSet.label,
    data: dataSet.points.map((point) => formatYAxis(point.y, chartData.schema.y)),
    fill: false,
  }));

  return {
    labels,
    datasets,
  };
};

function getTimestampFormat(unit?: DateRangeValues) {
  switch (unit) {
    case 'today':
      return 'hh:mm A';
    case 'last_7_days':
      return 'MMM DD';
    case 'last_30_days':
      return 'MMM DD';
    default:
      return 'MMM DD';
  }
}
