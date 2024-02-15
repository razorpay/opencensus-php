import { useMemo, useState, useCallback, useRef } from 'react';
import { Button, UploadIcon, PlusIcon, EyeIcon, Link } from '@razorpay/blade/components';

import { useSplitzService } from 'common/splitz';
import { paymentId, amount, createdAt, status } from 'common/ui/item/pair';
import EntityTable from 'merchant/components/EntityTable';
import SenderDetails from 'merchant/views/Transactions/v1/B2bPayments/components/SenderDetails';
import { getCountryName } from 'merchant/views/Transactions/v1/B2bPayments/utils';
import { makeIdLink } from 'merchant/views/Transactions/v1/Payments/Utils';
import './styles.styl';

const _paymentId = (splitz) => {
  return {
    title: paymentId.title,
    value: (item) => {
      const intermediateElement = makeIdLink('payment')(
        item,
        'Transactions.Invoices',
        'invoices-table',
        splitz,
      );
      return <div>{intermediateElement}</div>;
    },
  };
};

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
  isSenderDetailsEnabled,
  ...props
}) => {
  const splitz = useSplitzService();
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

  const senderDetailsColumn = {
    title: 'Sender Details',
    value: (item) => {
      const { sender_address } = item ?? {};
      const { name = '', country = '' } = sender_address ?? {};
      const countryName = getCountryName(country);

      return <SenderDetails name={name} country={countryName} />;
    },
  };

  const tableColumns = [
    _paymentId(splitz),
    amount,
    createdAt,
    paymentMethodColumn,
    status,
    actionColumn,
  ];

  if (isSenderDetailsEnabled) {
    /**
     * Insert senderDetailsColumn before the last element (at length - 1)
     * array.splice(indexToInsertAt, 0, elementToInsert);
     */
    tableColumns.splice(tableColumns.length - 1, 0, senderDetailsColumn);
  }

  return (
    <>
      <EntityTable title="Payments" columns={tableColumns} {...props} />
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
