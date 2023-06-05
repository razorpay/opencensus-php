import { CSSProperties } from 'react';
import { Theme } from '@razorpay/blade/components';

export interface CollapsibleArrayPropsType {
  arr: string[];
  logId?: string;
  scheduleId?: string;
}
export interface TableTemplateType<RowType, AdditionalInfoType = void> {
  /**
   * Array of headers(th) for the table
   */
  headers: string[];
  cells: {
    /**
     * Style for cell
     */
    style?: CSSProperties;
    /**
     * Component to render in the cell
     */
    render: (
      /**
       * Data for the cell
       */
      data: RowType,
      index: number,
      /**
       * Blade's `theme` object
       */
      theme: Theme,
      additionalInfo?: AdditionalInfoType,
    ) => JSX.Element;
  }[];
}

export interface ReportTablePropsType<RowType, AdditionalInfoType> {
  /**
   * A defined object that contains all the data on how the table should be rendered.
   */
  template: TableTemplateType<RowType, AdditionalInfoType>;
  /**
   * Array of data containing data for each row
   */
  rows: RowType[];
  /**
   * When true, table will have a fixed height and all the headers will be fixed so that it doesn't go outside the view port.
   */
  fixedHeaders?: boolean;
  onPageChange?: (x: number) => void;
  /**
   * Data to be passed additionally to the individual column
   */
  additionalInfo?: AdditionalInfoType;
  /**
   * An array containing indexes of table headers to be centered horizontally.
   */
  centeredHeaders?: number[];
  /**
   * Loading state, when true, will show contents via "onLoadingSkeletonTemplate", else "template".
   */
  loading?: boolean;
  /**
   * A component to render when the table is empty.
   */
  renderOnEmpty?: () => JSX.Element;
  /**
   * Total count of rows (for pagenation)
   */
  totalRows: number;
  /**
   * A template with skeleton component's info wrt to each cells.
   */
  onLoadingSkeletonTemplate?: TableTemplateType<RowType, AdditionalInfoType>;
  /**
   * Page currently on.
   */
  currentPage: number;
  /**
   * Number of rows to show in each page
   */
  pageSize?: number;
}
