import React from 'react';
import lazy from 'merchant/routes/LazyLoader';

// analytics
import {
  trackFilterSubmit,
  trackSearchClicked,
  trackSearchClear,
  trackInvoiceUploadClick,
  trackInvoiceUploadStatus,
  trackInvoiceViewClick,
  trackInvoiceViewStatus,
  trackShown,
} from 'merchant/views/Transactions/UploadInvoice/analytics';

// components
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import Button from 'common/new-ui/Button';
// eslint-disable-next-line no-restricted-imports
import HeaderAction from 'common/ui/HeaderAction';
import ListContainer from 'merchant/containers/ListContainer';
import PaymentTable from 'merchant/views/Transactions/UploadInvoice/components/PaymentTable';
import ListFilter from 'merchant/views/Transactions/B2bPayments/components/ListFilter';
import EmptyComponent from 'merchant/views/Transactions/B2bPayments/components/EmptyComponent';
import InfoBanner from 'merchant/views/Transactions/B2bPayments/components/InfoBanner';
import ErrorBoundary, { Teams, Ranks } from 'common/new-ui/ErrorBoundary';

// actions
import { uploadInvoice, viewInvoice } from 'merchant/reducers/paymentUploadInvoice';

const BulkUploadModal = lazy(() =>
  import(
    /* webpackChunkName: 'BulkUploadModal' */ 'merchant/views/Transactions/UploadInvoice/BulkUpload'
  ),
);

class PaymentsListContainer extends ListContainer {
  onFilterSubmit = (params) => {
    this.search(params)
      ?.then(() => {
        trackFilterSubmit({
          paymentId: params.id,
          paymentStatus: params.status,
          emailFilled: Boolean(params.email),
          notesFilled: Boolean(params.notes),
          count: params.count,
          resultsReturned: true,
          status: 'success',
        });
      })
      .catch(() => {
        trackFilterSubmit({
          paymentId: params.id,
          paymentStatus: params.status,
          emailFilled: Boolean(params.email),
          notesFilled: Boolean(params.notes),
          count: params.count,
          resultsReturned: false,
          status: 'failure',
        });
      });
  };

  onSearchAnalytics = (params) => {
    trackSearchClicked({
      paymentId: params.id,
      paymentStatus: params.status,
    });
  };

  onClearAnalytics = () => {
    trackSearchClear();
  };

  refreshList = () => {
    this.paginate({});
  };

  onUploadInvoice = async (id, file) => {
    const { uploadInvoicePending, uploadInvoiceSuccess, uploadInvoiceError, showNotification } =
      this.props;
    uploadInvoicePending({ id });
    trackInvoiceUploadClick({
      paymentId: id,
    });
    try {
      await uploadInvoice(id, file);
      uploadInvoiceSuccess({ id });
      this.refreshList();
      showNotification({
        type: 'success',
        message: 'File uploaded successfully',
      });
      trackInvoiceUploadStatus({
        paymentId: id,
        status: 'success',
      });
    } catch (err) {
      uploadInvoiceError({ id });
      showNotification({
        type: 'error',
        message: err?.message || 'Failed to upload file. Please try again!',
      });
      trackInvoiceUploadStatus({
        paymentId: id,
        status: 'failure',
      });
    }
  };

  onView = async (id) => {
    const { viewInvoicePending, viewInvoiceSuccess, viewInvoiceError, showNotification } =
      this.props;
    viewInvoicePending({ id });
    trackInvoiceViewClick({
      documentId: id,
    });
    try {
      const response = await viewInvoice(id);
      if (response.data?.url) {
        window.open(response.data?.url, '_blank');
        trackInvoiceViewStatus({
          documentId: id,
          status: 'success',
        });
        viewInvoiceSuccess({ id });
      }
    } catch (err) {
      const errors = err?.errors ?? [];
      const message = Array.isArray(errors)
        ? errors.join(' ')
        : 'Failed to fetch invoice details. Please try again!';

      viewInvoiceError({ id });
      showNotification({
        type: 'error',
        message,
      });
      trackInvoiceViewStatus({
        documentId: id,
        status: 'failure',
      });
    }
  };

  onBulkUpload = () => {
    const { openModal } = this.props;

    openModal({
      component: (
        <SuspenseWithLoader>
          <BulkUploadModal refreshList={this.refreshList} />
        </SuspenseWithLoader>
      ),
      overlayStyles: { display: 'flex', justifyContent: 'center', alignItems: 'center' },
    });
  };

  componentDidMount() {
    trackShown();
  }

  render() {
    const { invoiceFetching, invoiceUploading, ...rest } = this.props;
    const { skip, count } = this.state;

    return (
      <ErrorBoundary resetOnProps rank={Ranks.P1} team={Teams.CROSS_BORDER}>
        <div className="content-wrapper">
          <HeaderAction>
            <div className="btn-toolbar pull-right">
              <a
                className="btn btn-link"
                href="https://razorpay.com/docs/payments/dashboard/upload-invoices/"
                target="_blank"
                rel="noopener noreferrer"
              >
                Guide to Upload Invoice <i className="i i-external-link" />
              </a>
            </div>
            <Button.Primary onClick={this.onBulkUpload}>Bulk Upload</Button.Primary>
          </HeaderAction>
          <ListFilter
            form="uploadInvoicePaymentListFilter"
            count={count}
            onSubmit={this.onFilterSubmit}
            onSearchAnalytics={this.onSearchAnalytics}
            onClearAnalytics={this.onClearAnalytics}
          />
          <InfoBanner text="Uploading an invoice and AWB copy (if applicable) for international payments is required for audit purposes as per RBI guidelines. Without a valid invoice and AWB copy (if applicable) for each transaction, your settlements cannot be processed and will be put on hold if the invoice copy is not received within 15 days." />
          <PaymentTable
            {...rest}
            count={count}
            skip={skip}
            EmptyComponent={EmptyComponent}
            uploadState={invoiceUploading}
            invoiceFetching={invoiceFetching}
            paginate={this.paginate}
            onView={this.onView}
            onUpload={this.onUploadInvoice}
          />
        </div>
      </ErrorBoundary>
    );
  }
}

export default PaymentsListContainer;
