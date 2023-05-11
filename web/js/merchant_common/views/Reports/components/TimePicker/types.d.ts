import moment from 'moment';
import { NecessityIndicatorType } from 'merchant_common/views/Reports/components/types';

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
}

export interface TimePickerCalPropsType extends BaseTimePickerProps {
  value: moment.Moment;
}

export interface TimePickerPropsType extends BaseTimePickerProps {
  defaultValue?: moment.Moment;
}
