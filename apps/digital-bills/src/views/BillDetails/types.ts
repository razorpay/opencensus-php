import { Bill } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

export type BillByIdResponse = {
  billById: Bill;
};

export type BillBulkDeleteResponse = {
  billBulkDelete: {
    code: number;
    message: string;
    success: boolean;
  };
};
