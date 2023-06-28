export * from './StatusIndicator/types';
export * from './CollapsibleForm/types';
export * from './DateTimeRangePicker/types';
export * from './MultiSelectDropdown/types';
export * from './EmptyTable/types';
export * from './ReportModal/types';
export * from './Pagination/types';
export * from './Switch/types';
export * from './Tabs/types';
export * from './Table/types';
export * from './TimePicker/types';
export * from './MonthPicker/types';
export * from './YearPicker/types';
export * from './NecessityIndicator/types';

export type BaseValidationStyledProps = {
  validation?: boolean;
  focused?: boolean;
  label?: string;
  open?: boolean;
};

export interface ActionListComponentProps<T> {
  options: T[];
  itemComponent: (x: { data: T; index: number }) => JSX.Element;
  /**
   * For reports default is 2.
   */
  surfaceLevel?: 2 | 3 | undefined;
}
