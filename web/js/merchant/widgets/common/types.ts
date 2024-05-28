import { DateRangeValues } from './Select/types';

export interface ErrorStateProps {
  text?: string;
  retryHandler?: () => void;
  analyticsProperties?: any;
}

export enum durationOptionsMap {
  'today' = 'Today',
  'last_7_days' = 'This week',
  'last_30_days' = 'This month',
}

export interface CommonInputProps {
  value: any;
  onChange: (value: any) => any;
}

export type PointType = {
  x: string;
  y: number;
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
