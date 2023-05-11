import { NecessityIndicatorType } from 'merchant_common/views/Reports/components/types';

export interface YearPickerProps {
  value: number | undefined;
  onChange: (x: number) => void;
  helpText?: string;
  errorText?: string;
  label?: string;
  /**
   * Function when called, returns the validation state
   */
  validate?: () => boolean;
  placeHolder?: string;
  /**
   * For disabling all the dates after today
   */
  disableFuture?: boolean;
  /**
   * For disabling all the dates upto today
   */
  disablePast?: boolean;
  necessityIndicator?: NecessityIndicatorType;
}
