import moment from 'moment';
import {
  NecessityIndicatorType,
  MinutesInterval,
} from 'merchant_common/views/Reports/components/types';

enum Meridiem {
  'AM' = 'AM',
  'PM' = 'PM',
}

export type MeridiemType = keyof typeof Meridiem;

export interface TimeInfoPropsType {
  chevUpClick: () => void;
  chevDownClick: () => void;
  children: string | number;
  ariaLabel?: string;
  role?: string;
}

export interface TimePickerRes {
  date: moment.Moment;
  renderInfo: {
    hour: number;
    minutes: number;
    meridiem: MeridiemType;
  };
}

export interface BaseTimePickerProps {
  onChange: (x: TimePickerRes) => void;
  disableInput?: boolean;
  helpText?: string;
  label?: string;
  validate?: () => boolean;
  errorText?: string;
  necessityIndicator?: NecessityIndicatorType;
  minutesInterval?: MinutesInterval;
}

export interface TimePickerCalPropsType extends BaseTimePickerProps {
  value: moment.Moment;
}

export interface TimePickerPropsType extends BaseTimePickerProps {
  defaultValue?: moment.Moment;
}

enum MinutesChangeType {
  'increase' = 'increase',
  'decrease' = 'decrease',
}
export type MinutesModifierFn = (
  action: keyof typeof MinutesChangeType,
  x: number,
  fn: (y: number) => void,
  y?: MinutesInterval,
) => void;

export type PickerInfoProps = Pick<
  TimePickerPropsType,
  'disableInput' | 'minutesInterval' | 'onChange'
> & {
  defaultValue: moment.Moment;
};

export type TimePickerContextType = {
  hour: number;
  minutes: number;
  meridiem: MeridiemType;
  shouldShowPicker: boolean;
  hourChevUpClick: () => void;
  hourChevDownClick: () => void;
  minutesChevUpClick: () => void;
  minutesChevDownClick: () => void;
  meridiemChevUpClick: () => void;
  meridiemChevDownClick: () => void;
  setShowPicker: (x?: boolean) => void;
  selectedTime: moment.Moment;
};
