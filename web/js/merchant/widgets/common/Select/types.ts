import { CommonInputProps } from 'merchant/widgets/common/types';

export interface DateRangeInput {
  type: 'select';
  values: Array<DateRangeValues>;
  default_value: DateRangeValues;
}

export type SelectProps = CommonInputProps &
  DateRangeInput & { analyticsProperties: Record<string, any> };

export interface SelectChangeEvent {
  name?: string;
  values: Array<string>;
}

export type DateRangeValues = 'today' | 'last_7_days' | 'last_30_days';
