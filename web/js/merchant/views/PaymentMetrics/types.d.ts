export type Preset = {
  label: string;
  name: string;
  value: mumber;
  unit: string;
};

export type Filter = {
  startDate: moment.Moment;
  endDate: moment.Moment;
  preset: Preset;
};

export type PaymentMetricsReducerProps = {
  filters: Filter;
  interval: string;
  chartData: Record<string, { isLoading: boolean; error: string; datasets: Array<LineData> }>;
  selectedMetric: string;
  selectedMetricFilters: Filter;
  selectedMetricInterval: string;
};

export type User = {
  merchant: {
    category2: string | null;
  };
};

export type AllPaymentMetricsProps = {
  paymentMetrics: PaymentMetricsReducerProps;
  user: User;
  updateInterval: (string) => any;
  updateDateRange: (Filter) => any;
  resetPaymentDashboard: () => any;
};

export type PaymentMetricsFilterProps = Filter & {
  updateDateRange: (Filter) => any;
  updateInterval: (string) => any;
};

export type LoadingErrorProps = {
  isLoading?: boolean;
  noData?: boolean;
  error?: string;
  showDescription?: boolean;
};

export type OverallCrProps = {
  paymentMetrics: PaymentMetricsReducerProps;
  getOverallCR: (Filter) => any;
  category: string;
  selectedMetricsUpdateDateRange: (Filter) => any;
  selectedMetricsUpdateInterval: (string) => any;
  setSelectedMetric: (string) => any;
  getIndustryOverallCR: (Filter) => void;
};

export type GraphDataTokenTypes = {
  name: string;
  title: string;
  description: string;
  xLabel: string;
  yLabel: string;
  xAxisID: string;
  yAxisID: string;
};

export type LineData = {
  backgroundColor?: string;
  borderColor?: string;
  borderWidth?: number;
  fill?: boolean;
  label?: string;
  tagName?: string;
  xAxisID?: string;
  yAxisID?: string;
  data?: Array<PointData>;
};

export type GraphProps = Partial<GraphDataTokenTypes> &
  LoadingErrorProps & {
    interval: string;
    data: GraphData;
    btnAction?: (arg0: Array<PointData>) => any;
  };

export type MethodLevelCrProps = {
  paymentMetrics: PaymentMetricsReducerProps;
  getMethodLevelCR: (Filter) => any;
  interval: string;
  startDate: string;
  endDate: string;
};
export type SelectedMetricPanelProps = {
  paymentMetrics: PaymentMetricsReducerProps;
  selectedMetricsUpdateInterval: (string) => any;
  selectedMetricsUpdateDateRange: (Filter) => any;
  handleBack: () => any;
  selectedMetric: string;
};

export type ComparisonGraphs = {
  crData: Record<string, number>;
  isFetching: boolean;
  error: string;
  industryData?: Record<string, number>;
};
