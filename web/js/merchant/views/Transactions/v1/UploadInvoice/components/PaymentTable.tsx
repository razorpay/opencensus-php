import React, { useMemo, useState, useCallback, useRef } from 'react';

import Button from 'common/new-ui/Button';
import { useSplitzService } from 'common/splitz';
import { paymentId, amount, createdAt, status } from 'common/ui/item/pair';
import EntityTable from 'merchant/components/EntityTable';
import { makeIdLink } from 'merchant/views/Transactions/v1/Payments/Utils';
import { AWB_PURPOSE_CODES } from 'merchant/views/Transactions/v1/UploadInvoice/BulkUpload/constants';

import { UPLOAD_INVOICES_TYPE } from './constants';

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
  purposeCode: string;
  paginate?: (
    params: Record<string, string | number | boolean | undefined>,
  ) => Promise<unknown> | null | undefined;
  onUpload: (paymentId: string, type: string, file: File) => void;
  onView: (documentId: string) => void;
}

const ListTable = ({
  onUpload,
  onView,
  uploadState,
  invoiceFetching,
  purposeCode,
  ...props
}: ListTableProps): JSX.Element => {
  const [selectedPayment, setSelectedPayment] = useState<{ id: string; type: string } | null>(null);
  const fileUploaderRef = useRef<HTMLInputElement | null>(null);
  const splitz = useSplitzService();

  const handleUploadClick = useCallback((id, type) => {
    setSelectedPayment({ id, type });
    if (fileUploaderRef.current) {
      if (type === UPLOAD_INVOICES_TYPE.OPGSP_AWB) {
        fileUploaderRef.current.accept = 'application/pdf';
      } else {
        fileUploaderRef.current.accept = 'image/jpeg,image/png,application/pdf';
      }
      fileUploaderRef.current.click();
    }
  }, []);

  const handleFileSelect = useCallback(
    (e) => {
      if (e.target?.files?.length && selectedPayment?.id) {
        onUpload(selectedPayment.id, selectedPayment.type, e.target.files[0]);
        setSelectedPayment(null);
      }
    },
    [onUpload, selectedPayment],
  );

  const uploadInvoiceColumn = useMemo(
    () => ({
      title: 'Actions',
      value: ({ opgsp_invoice_doc, id, status }: PaymentItem = {}) => {
        const docType = UPLOAD_INVOICES_TYPE.OPGSP_INVOICE;
        if (id && uploadState?.[`${id}-${docType}`]) {
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
            <Button.Transparent iconBefore="upload" onClick={() => handleUploadClick(id, docType)}>
              Upload Invoice
            </Button.Transparent>
          );
        }
        return null;
      },
    }),
    [handleUploadClick, onView, uploadState, invoiceFetching],
  );

  const uploadAwbColumn = useMemo(
    () => ({
      title: '',
      value: ({ opgsp_awb_doc, id, status }: PaymentItem = {}) => {
        const docType = UPLOAD_INVOICES_TYPE.OPGSP_AWB;
        if (!AWB_PURPOSE_CODES.includes(purposeCode)) return null;
        if (id && uploadState?.[`${id}-${docType}`]) {
          return <Button.Transparent>Uploading...</Button.Transparent>;
        }
        if (opgsp_awb_doc && invoiceFetching?.[opgsp_awb_doc]) {
          return 'Loading...';
        }

        if (opgsp_awb_doc) {
          return (
            <Button.Transparent
              className="invoice-payment-doc-view-btn"
              iconBefore="eye"
              onClick={() => onView(opgsp_awb_doc)}
            >
              View
            </Button.Transparent>
          );
        }
        if (status === 'authorized' || status === 'captured') {
          return (
            <Button.Transparent iconBefore="upload" onClick={() => handleUploadClick(id, docType)}>
              Upload Airway Bill
            </Button.Transparent>
          );
        }
        return null;
      },
    }),
    [handleUploadClick, onView, uploadState, invoiceFetching, purposeCode],
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
          uploadInvoiceColumn,
          uploadAwbColumn,
        ]}
        {...props}
      />
      <input
        type="file"
        ref={fileUploaderRef}
        className="invoice-file-uploader"
        data-testid="opgsp-file-uploader"
        onChange={handleFileSelect}
      />
    </>
  );
};

export default ListTable;
