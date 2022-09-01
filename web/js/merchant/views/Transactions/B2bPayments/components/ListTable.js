import { useMemo, useState, useCallback, useRef } from 'react';

// utils
import { paymentId, amount, createdAt, status } from 'common/ui/item/pair';

// components
import Button from 'common/new-ui/Button';
import EntityTable from 'merchant/components/EntityTable';

// styles
import './styles.styl';

// constants
const paymentMethodColumn = {
  title: 'Payment Method',
  value: () => 'Bank Transfer',
};

const ListTable = ({ onUpload, onView, uploadState, invoiceFetching, ...props }) => {
  const [selectedPaymentId, setSelectedPaymentId] = useState(null);
  const fileUploaderRef = useRef(null);

  const handleUploadClick = useCallback((id) => {
    setSelectedPaymentId(id);
    if (fileUploaderRef.current) {
      fileUploaderRef.current.click();
    }
  }, []);

  const handleFileSelect = useCallback(
    (e) => {
      if (e.target?.files?.length && selectedPaymentId) {
        onUpload(selectedPaymentId, e.target.files[0]);
        setSelectedPaymentId(null);
      }
    },
    [onUpload, selectedPaymentId],
  );

  const actionColumn = useMemo(
    () => ({
      title: 'Actions',
      value: ({ b2b_export_invoice, id, status } = {}) => {
        if (uploadState[id]) {
          return <Button.Transparent>Uploading...</Button.Transparent>;
        }
        if (b2b_export_invoice && invoiceFetching?.[b2b_export_invoice]) {
          return 'Loading...';
        }

        if (b2b_export_invoice) {
          return (
            <Button.Transparent
              className="b2b-payment-doc-view-btn"
              iconBefore="eye"
              onClick={() => onView(b2b_export_invoice)}
            >
              View
            </Button.Transparent>
          );
        }
        if (status === 'authorized') {
          return (
            <Button.Transparent iconBefore="upload" onClick={() => handleUploadClick(id)}>
              Upload
            </Button.Transparent>
          );
        }
        return null;
      },
    }),
    [handleUploadClick, onView, uploadState, invoiceFetching],
  );

  return (
    <>
      <EntityTable
        title="Payments"
        columns={[paymentId, amount, createdAt, paymentMethodColumn, status, actionColumn]}
        {...props}
      />
      <input
        type="file"
        ref={fileUploaderRef}
        className="b2b-file-uploader"
        accept="application/msword, application/vnd.ms-excel, text/plain, application/pdf, image/*"
        onChange={handleFileSelect}
      />
    </>
  );
};

export default ListTable;
