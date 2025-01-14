import { DateRangeValues } from './Select/types';

export interface ErrorStateProps {
  text?: string;
  retryHandler?: () => void;
  analyticsProperties?: any;
}

export const durationOptionKeys = {
  TODAY: 'today',
  LAST_7_DAYS: 'last_7_days',
  LAST_30_DAYS: 'last_30_days',
  CUSTOM: 'custom_range',
} as const;

export const durationOptionsMap = {
  [durationOptionKeys.TODAY]: 'Today',
  [durationOptionKeys.LAST_7_DAYS]: 'This week',
  [durationOptionKeys.LAST_30_DAYS]: 'This month',
};

export interface CommonInputProps {
  value: any;
  onChange: (value: any, custom_range: any) => any;
}

export type PointType = {
  x: string;
  y: string;
};

export type ChartDatasetType = {
  label: string;
  points: Array<PointType>;
};

export type ChartSchemaType = {
  x: {
    type: string;
    unit: string;
  };
  y: {
    type: string;
    unit: string;
  };
};

export type ChartDataType = {
  type: string;
  labels: Array<string>;
  schema: ChartSchemaType;
  data: Array<ChartDatasetType>;
};

export interface ChartProps {
  chartData: ChartDataType;
  unit?: DateRangeValues;
}

export interface Dataset {
  label: string;
  data: Array<number>;
  fill: boolean;
  backgroundColor?: Array<string>;
  borderWidth?: number;
  borderColor?: string;
  schema: ChartSchemaType;
  currency_symbol: string | undefined;
}
