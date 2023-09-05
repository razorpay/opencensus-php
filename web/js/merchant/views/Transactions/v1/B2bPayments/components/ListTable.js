import { useMemo, useState, useCallback, useRef } from 'react';

// utils
import { paymentId, amount, createdAt, status } from 'common/ui/item/pair';

// components
import EntityTable from 'merchant/components/EntityTable';
import { Button, UploadIcon, PlusIcon, EyeIcon, Link } from '@razorpay/blade/components';

// styles
import './styles.styl';

// constants
const paymentMethodColumn = {
  title: 'Payment Method',
  value: () => 'Bank Transfer',
};

const ListTable = ({
  onUpload,
  onView,
  uploadState,
  invoiceFetching,
  onBuyerAddressClick,
  ...props
}) => {
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
        const handleView = () => onView(b2b_export_invoice);
        const handleBuyerAddress = () => onBuyerAddressClick(id);
        const handleUpload = () => handleUploadClick(id);

        if (uploadState[id]) {
          return 'Uploading...';
        }
        if (b2b_export_invoice && invoiceFetching?.[b2b_export_invoice]) {
          return 'Loading...';
        }

        if (b2b_export_invoice) {
          return (
            <>
              <Button
                className="b2b-payment-doc-view-btn"
                variant="tertiary"
                size="xsmall"
                icon={EyeIcon}
                onClick={handleView}
              >
                View
              </Button>
              {status !== 'captured' && (
                <Link
                  variant="button"
                  icon={PlusIcon}
                  size="medium"
                  marginLeft="spacing.5"
                  onClick={handleBuyerAddress}
                >
                  Add/Update Buyer Address
                </Link>
              )}
            </>
          );
        }
        if (status === 'authorized') {
          return (
            <>
              <Link variant="button" icon={UploadIcon} size="medium" onClick={handleUpload}>
                Upload
              </Link>
              <Link
                variant="button"
                icon={PlusIcon}
                size="medium"
                marginLeft="spacing.5"
                onClick={handleBuyerAddress}
              >
                Add/Update Buyer Address
              </Link>
            </>
          );
        }
        return null;
      },
    }),
    [uploadState, invoiceFetching, onView, handleUploadClick, onBuyerAddressClick],
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
        data-testid="b2b-file-uploader"
      />
    </>
  );
};

export default ListTable;
