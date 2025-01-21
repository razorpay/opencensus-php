import { TableColumns } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/stores/billsTableConfigStore';

import type {
  Bill,
  PageLimitType,
} from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

export type BillsTableComponentProps = {
  editColumnModalProps: {
    isEditColumnOpen: boolean;
    dismissEditColumn: () => void;
    openEditColumn: () => void;
  };
  deleteModalProps: {
    isDeleteModalOpen: boolean;
    dismissDeleteModal: () => void;
    openDeleteModal: () => void;
  };
  tableProps: {
    isRefreshing: boolean;
    billsData: Bill[];
    totalItemCount: number;
    isTableSelectable: boolean;
    setIsTableSelectable: (state: boolean) => void;
    selectedBills: Bill[];
    setSelectedBills: (bills: Bill[]) => void;
    changePage: (offset: number) => void;
    defaultPageSize: PageLimitType;
    tableColumns: TableColumns[];
    currentPage: number;
  };
};
