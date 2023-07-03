import moment from 'moment';
import {
  NecessityIndicatorType,
  MinutesInterval,
} from 'merchant_common/views/Reports/components/types';
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
    meridiem: 'AM' | 'PM';
  };
}

export interface BaseTimePickerProps {
  onChange: (x: TimePickerRes) => void;
  disableInput?: boolean;
  helpText?: string;
  label?: string;
  validate?: () => boolean;
  /**
   * A callback called when the picker is closed
   */
  onClose?: () => void;
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
