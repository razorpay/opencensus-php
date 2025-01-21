import create from 'zustand';

import {
  BILL_AMOUNT_COL_KEY,
  BILL_AMOUNT_COL_NAME,
  BILL_CONTACT_COL_KEY,
  BILL_CONTACT_COL_NAME,
  BILL_DATE_COL_KEY,
  BILL_DATE_COL_NAME,
  BILL_EMAIL_COL_KEY,
  BILL_EMAIL_COL_NAME,
  BILL_ID_COL_KEY,
  BILL_ID_COL_NAME,
  BILL_STATUS_COL_KEY,
  BILL_STATUS_COL_NAME,
  BILL_STORE_COL_KEY,
  BILL_STORE_COL_NAME,
  BILL_TRANSACTION_COL_KEY,
  BILL_TRANSACTION_COL_NAME,
} from '@apps/digital-bills/src/utils/constants';

type EditableColumns = {
  id: string;
  name: string;
  isSelected: boolean;
};

export type TableColumns = {
  key: string;
  name: string;
};

const editableColumns = [
  { id: BILL_ID_COL_KEY, name: BILL_ID_COL_NAME, isSelected: true },
  { id: BILL_CONTACT_COL_KEY, name: BILL_CONTACT_COL_NAME, isSelected: true },
  { id: BILL_DATE_COL_KEY, name: BILL_DATE_COL_NAME, isSelected: true },
  { id: BILL_STORE_COL_KEY, name: BILL_STORE_COL_NAME, isSelected: true },
  { id: BILL_AMOUNT_COL_KEY, name: BILL_AMOUNT_COL_NAME, isSelected: true },
  { id: BILL_TRANSACTION_COL_KEY, name: BILL_TRANSACTION_COL_NAME, isSelected: true },
  { id: BILL_STATUS_COL_KEY, name: BILL_STATUS_COL_NAME, isSelected: true },
  { id: BILL_EMAIL_COL_KEY, name: BILL_EMAIL_COL_NAME, isSelected: false },
];

const tableColumns = [
  { key: BILL_ID_COL_KEY, name: BILL_ID_COL_NAME },
  { key: BILL_CONTACT_COL_KEY, name: BILL_CONTACT_COL_NAME },
  { key: BILL_DATE_COL_KEY, name: BILL_DATE_COL_NAME },
  { key: BILL_STORE_COL_KEY, name: BILL_STORE_COL_NAME },
  { key: BILL_AMOUNT_COL_KEY, name: BILL_AMOUNT_COL_NAME },
  { key: BILL_TRANSACTION_COL_KEY, name: BILL_TRANSACTION_COL_NAME },
  { key: BILL_STATUS_COL_KEY, name: BILL_STATUS_COL_NAME },
];

type BillsTableConfigState = {
  editableColumns: EditableColumns[];
  tableColumns: TableColumns[];
  sortColumns: (sortedColumns: EditableColumns[]) => void;
  toggleColumnSelection: (isSelected: boolean, id: string) => void;
  setTableColumns: (sortedColumns: TableColumns[]) => void;
};
// #TODO: Add persist
export const useBillsTableConfigStore = create<BillsTableConfigState>((set) => ({
  editableColumns,
  tableColumns,
  sortColumns: (sortedColumns) => set((state) => ({ ...state, editableColumns: sortedColumns })),
  toggleColumnSelection: (isSelected, id) =>
    set((state) => {
      const modifiedColumns = state.editableColumns.map((column) => {
        if (column.id === id) return { ...column, isSelected };
        return column;
      });
      return { editableColumns: modifiedColumns };
    }),
  setTableColumns: (sortedColumns) => set((state) => ({ ...state, tableColumns: sortedColumns })),
}));
