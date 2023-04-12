export interface TextInputProps {
  label: string;
  helpText: string;
  value: string | undefined;
  /**
   * Function when called, returns the validation state
   */
  validate?: () => boolean;
  onChange: (val: string) => void;
  placeHolder: string;
  ariaLabel?: string;
}
