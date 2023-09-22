import React, { useMemo, useState, useCallback, useRef } from 'react';

import Button from 'common/new-ui/Button';
import { useSplitzService } from 'common/splitz';
import { paymentId, amount, createdAt, status } from 'common/ui/item/pair';
import EntityTable from 'merchant/components/EntityTable';
import { makeIdLink } from 'merchant/views/Transactions/v1/Payments/Utils';

import type { PaymentItem } from 'merchant/views/Transactions/v1/UploadInvoice/types';

// styles
import 'merchant/views/Transactions/v1/UploadInvoice/components/styles.styl';

const _paymentId = (splitz) => {
  return {
    title: paymentId.title,
    value: (item) => {
      const intermediateElement = makeIdLink('payment')(
        item,
        'Transactions.UploadInvoices',
        'upload-invoices-table',
        splitz,
      );
      return <div>{intermediateElement}</div>;
    },
  };
};

const paymentMethod = {
  title: 'Payment Method',
  value: (item: PaymentItem) =>
    item.method ? item.method.charAt(0).toUpperCase() + item.method.slice(1) : '',
};

const invoiceNumber = {
  title: 'Invoice Number',
  value: (item: PaymentItem) => item.notes?.invoice_number ?? '',
};

interface ListTableProps {
  count?: number;
  skip?: number;
  uploadState: Record<string, boolean>;
  invoiceFetching: Record<string, boolean>;
  EmptyComponent?: JSX.Element;
  paginate?: (
    params: Record<string, string | number | boolean | undefined>,
  ) => Promise<unknown> | null | undefined;
  onUpload: (paymentId: string, file: File) => void;
  onView: (documentId: string) => void;
}

const ListTable = ({
  onUpload,
  onView,
  uploadState,
  invoiceFetching,
  ...props
}: ListTableProps): JSX.Element => {
  const [selectedPaymentId, setSelectedPaymentId] = useState<string | null>(null);
  const fileUploaderRef = useRef<HTMLInputElement | null>(null);
  const splitz = useSplitzService();

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
      value: ({ opgsp_invoice_doc, id, status }: PaymentItem = {}) => {
        if (id && uploadState?.[id]) {
          return <Button.Transparent>Uploading...</Button.Transparent>;
        }
        if (opgsp_invoice_doc && invoiceFetching?.[opgsp_invoice_doc]) {
          return 'Loading...';
        }

        if (opgsp_invoice_doc) {
          return (
            <Button.Transparent
              className="invoice-payment-doc-view-btn"
              iconBefore="eye"
              onClick={() => onView(opgsp_invoice_doc)}
            >
              View
            </Button.Transparent>
          );
        }
        if (status === 'authorized' || status === 'captured') {
          return (
            <Button.Transparent iconBefore="upload" onClick={() => handleUploadClick(id)}>
              Upload Invoice
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
        columns={[
          _paymentId(splitz),
          invoiceNumber,
          amount,
          createdAt,
          paymentMethod,
          status,
          actionColumn,
        ]}
        {...props}
      />
      <input
        type="file"
        ref={fileUploaderRef}
        className="invoice-file-uploader"
        accept="image/jpeg, image/png, application/pdf"
        onChange={handleFileSelect}
      />
    </>
  );
};

export default ListTable;
