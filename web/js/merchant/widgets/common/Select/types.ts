import { CommonInputProps, durationOptionKeys } from 'merchant/widgets/common/types';

export interface DateRangeInput {
  type: 'select';
  values: Array<DateRangeValues>;
  default_value: DateRangeValues;
}

export type SelectProps = CommonInputProps &
  DateRangeInput & {
    analyticsProperties: Record<string, any>;
    customRange?: boolean;
    toggleHelpWidget?: ({ showWidget }: { showWidget: boolean }) => void;
  };

export interface SelectChangeEvent {
  name?: string;
  values: Array<string>;
}

export type DateRangeValues = (typeof durationOptionKeys)[keyof typeof durationOptionKeys];
