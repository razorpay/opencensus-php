export type MonthIndex = 0 | 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9 | 10 | 11;

export interface MonthPickerProps {
  value: MonthIndex | undefined;
  onChange: (x: MonthIndex) => void;
  helpText?: string;
  label?: string;
  /**
   * Function when called, returns the validation state
   */
  validate?: () => boolean;
  placeHolder?: string;
}
