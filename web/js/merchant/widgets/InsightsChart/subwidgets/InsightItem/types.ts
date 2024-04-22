import { LinkWidgetProps } from 'merchant/widgets/common/Link/types';
import { DateRangeValues } from 'merchant/widgets/common/Select/types';
import { CommonWidgetProps } from 'merchant/widgets/types';

export type PointType = {
  x: string;
  y: number;
};

export type ChartDatasetType = {
  label: string;
  points: Array<PointType>;
};

type XType = 'timestamp' | 'string';
type YType = 'number' | 'amount';

export type ChartSchemaType = {
  x: {
    type: XType;
    unit: string;
  };
  y: {
    type: YType;
    unit: string;
  };
};

export type ChartDataType = {
  type: 'line' | 'doughnut';
  labels: Array<string>;
  schema: ChartSchemaType;
  data: Array<ChartDatasetType>;
};

type Value = number;
type ValueType = 'percentage' | 'number' | 'amount';

export type Change = number;
export type ChangeType = 'percentage' | 'number';

export interface InsightItemProps extends CommonWidgetProps {
  title: string;
  tooltip_text: string;
  type: string;
  data: {
    value: Value;
    value_type: ValueType;
    currency: string;
    change: Change;
    change_type: ChangeType;
    change_behavior_inverted: boolean;
    chart_data: ChartDataType;
    sub_text: string;
  };
  action: LinkWidgetProps;
  date: DateRangeValues;
}

export interface CTATextProps {
  value: Value;
  value_type: ValueType;
  currency: string;
}
