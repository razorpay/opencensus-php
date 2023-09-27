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
  handleAddMore?: () => void;
  EmptyComponent?: React.ReactNode;
}

export function MagicDataTable<TData>({
  data,
  columns,
  customClass = 'settings-table',
  maxWidth = 'auto',
  addMoreLabel,
  handleAddMore,
  EmptyComponent,
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
      />
      {addMoreLabel && handleAddMore && (
        <AddMoreButton data-testid="magic-add-more-button" onClick={handleAddMore}>
          + Create more {addMoreLabel}
        </AddMoreButton>
      )}
    </DataTableWrapper>
  );
}

export default MagicDataTable;
