import React from 'react';
import DataTable from 'common/ui/Table/DataTable';
import { AddMoreButton, DataTableWrapper } from './styles';
import { ColumnDef } from './types';

export interface DataTableProps<TData> {
  data: TData[] | [];
  columns: ColumnDef<TData>[];
  customClass?: string;
  maxWidth?: string;
  addMoreLabel?: string;
  addMoreLabel2?: string;
  handleAddMore?: () => void;
  handleAddMoreViaFileUpload?: () => void;
  EmptyComponent?: React.ReactNode;
  gridTemplateColumns?: string;
}

export function MagicDataTable<TData>({
  data,
  columns,
  customClass = 'settings-table',
  maxWidth = 'auto',
  addMoreLabel,
  addMoreLabel2,
  handleAddMore,
  handleAddMoreViaFileUpload,
  EmptyComponent,
  ...props
}: DataTableProps<TData>): JSX.Element {
  const columnsWithClassName = columns.map((col) => ({
    ...col,
    columnClass: col.columnClass || 'magic-table-column',
  }));
  return (
    <DataTableWrapper maxWidth={maxWidth}>
      <DataTable
        EmptyComponent={EmptyComponent}
        customClass={customClass}
        items={data}
        columns={columnsWithClassName}
        {...props}
      />
      {addMoreLabel && handleAddMore && (
        <AddMoreButton data-testid="magic-add-more-button" onClick={handleAddMore}>
          + Create more {addMoreLabel}
        </AddMoreButton>
      )}
      {addMoreLabel2 && handleAddMoreViaFileUpload && (
        <AddMoreButton data-testid="magic-upload-more-button" onClick={handleAddMoreViaFileUpload}>
          + Upload {addMoreLabel2}
        </AddMoreButton>
      )}
    </DataTableWrapper>
  );
}

export default MagicDataTable;
