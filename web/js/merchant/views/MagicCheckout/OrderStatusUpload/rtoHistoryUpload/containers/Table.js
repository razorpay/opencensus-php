import DataTable from 'common/ui/Table/DataTable';
import {
  fileId,
  shippingProvider,
  createdAt,
  status,
} from 'merchant/views/MagicCheckout/OrderStatusUpload/rtoHistoryUpload/components/CellItem';

const RTOHistoryTable = ({ items, loading, error }) => (
  <DataTable
    title="RTO history Uploads"
    columns={[fileId, shippingProvider, createdAt, status]}
    items={items}
    loading={loading}
    error={error}
    customClass="rto-history-table"
  />
);

export default RTOHistoryTable;
