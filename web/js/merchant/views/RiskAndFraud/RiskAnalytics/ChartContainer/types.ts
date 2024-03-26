import { LinearComponentProps } from 'react-chartjs-2';

import {
  AnalyticsEntity,
  QueryResponseItem,
  DateRange,
  IntervalValue,
  MetricOptions,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/types';

export type SelectedGraphOption = 'total_sales' | 'entity' | 'entity_ratio';

export type ChartOption = { label: string; value: string };

export type ChartLabelsMapping = {
  [entity in AnalyticsEntity]: {
    [metric in MetricOptions]: { x: string; y1: string; y2: string };
  };
};

export type GenerateChartDataType = {
  entity: AnalyticsEntity;
  metric: MetricOptions;
  graphOptions: SelectedGraphOption[];
  queryData: QueryResponseItem[];
};

export type ChartDataset = {
  label: string;
  data: number[];
  backgroundColor: string;
  order: number;
  yAxisID: string;
  hidden: boolean;
  // line chart
  borderColor?: string;
  borderWidth?: number;
  pointRadius?: number;
  fill?: boolean;
  pointHoverRadius?: number;
  pointHoverBorderWidth?: number;
  type?: string;
  lineTension?: number;
};

export type ChartComponentType = React.ComponentType<LinearComponentProps>;
export type ChartDatasets = (ChartDataset | null)[];
export type DefaultChartData = { labels: []; datasets: [] };
export type ChartData = { labels: number[]; datasets: ChartDatasets } | DefaultChartData;

export type ChartContainerProps = {
  isLoading: boolean;
  isError: boolean;
  entity: AnalyticsEntity;
  dateRange: DateRange;
  selectedInterval: IntervalValue;
  metric: MetricOptions;
  graphOptions: SelectedGraphOption[];
  queryData: QueryResponseItem[];
  chartData?: ChartData;
  handleInterval: (value: IntervalValue) => void;
};

export type CalculateStepSizeResult = {
  min: number;
  max: number;
  stepSize: number;
  labels: number[];
};

export type GetChartAreaConfigParams = {
  entity: AnalyticsEntity;
  metric: MetricOptions;
  selectedInterval: string;
  chartData: ChartData;
};

export type YAxisPosition = 'left' | 'right';

type AxisTickCallback = (value: number) => number | string;

type XAxis = {
  id: string;
  fontFamily: string;
  categoryPercentage: number;
  barPercentage: number;
  border: { width: number };
  gridLines: { display: boolean };
  ticks: { maxRotation: number; fontColor: string; callback: AxisTickCallback };
};

type YAxis = {
  id: string;
  position: YAxisPosition;
  gridLines: { color?: string; display?: boolean };
  ticks: {
    fontFamily: string;
    beginAtZero: boolean;
    maxTicksLimit: number;
    min?: number;
    max?: number;
    stepSize?: number;
    labels?: number[];
    callback: AxisTickCallback;
  };
  scaleLabel: { display: boolean; labelString: string };
};

type ChartScales = { xAxes: XAxis[]; yAxes: YAxis[] };

type ChartLayout = { padding: { top: number; left: number; right: number; bottom: number } };

type ChartTooltipItem = {
  xLabel: number;
  yLabel: number;
  label: string;
  value: string;
  index: number;
  datasetIndex: number;
  x: number;
  y: number;
};

export type TooltipsConfig = {
  enabled?: boolean;
  mode?: 'point' | 'nearest' | 'index' | 'dataset';
  position?: 'average' | 'nearest';
  intersect?: boolean;
  bodySpacing?: number;
  borderWidth?: number;
  backgroundColor?: string;
  borderColor?: string;
  titleFontFamily?: string;
  titleFontColor?: string;
  titleSpacing?: number;
  titleMarginBottom?: number;
  bodyFontFamily?: string;
  bodyFontColor?: string;
  xPadding?: number;
  yPadding?: number;
  caretPadding?: number;
  cornerRadius?: number;
  callbacks?: {
    label?: (tooltipItem: ChartTooltipItem, data: ChartData) => string;
    title?: (tooltipItems: ChartTooltipItem[], data: ChartData) => string;
    afterLabel?: (tooltipItem: ChartTooltipItem, data: ChartData) => string | undefined;
    beforeLabel?: (tooltipItem: ChartTooltipItem, data: ChartData) => string | undefined;
  };
};

export type ChartConfigOptions = {
  responsive: boolean;
  maintainAspectRatio: boolean;
  layout: ChartLayout;
  scales: ChartScales;
  tooltips?: TooltipsConfig;
};
