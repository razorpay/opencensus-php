import moment from 'moment';

export enum Meridiem {
  AM = 'AM',
  PM = 'PM',
}

export interface TimeInfoPropsType {
  chevUpClick: () => void;
  chevDownClick: () => void;
  children: string | number;
  ariaLabel?: string;
  role?: string;
}

export interface TimePickerPropsType {
  date: moment.Moment;
  onChange: (x: {
    date: moment.Moment;
    renderInfo: {
      hour: number;
      minutes: number;
      meridiem: keyof typeof Meridiem;
    };
  }) => void;
  disableInput?: boolean;
  /**
   * A callback called when the picker is closed
   */
  onClose?: () => void;
}
