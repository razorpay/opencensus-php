import { CommonInputProps, durationOptionKeys } from 'merchant/widgets/common/types';
import { DateTime } from 'merchant/widgets/types';

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
    variables?: {
      id: string;
      date_time?: DateTime;
    };
  };

export interface SelectChangeEvent {
  name?: string;
  values: Array<string>;
}

export type DateRangeValues = (typeof durationOptionKeys)[keyof typeof durationOptionKeys];
