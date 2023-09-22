import React from 'react';

import Button from 'common/new-ui/Button';
import ErrorBoundary, { Teams, Ranks } from 'common/new-ui/ErrorBoundary';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import ListContainer from 'merchant/containers/ListContainer';
import { uploadInvoice, viewInvoice } from 'merchant/reducers/paymentUploadInvoice';
import lazy from 'merchant/routes/LazyLoader';
import EmptyComponent from 'merchant/views/Transactions/v1/B2bPayments/components/EmptyComponent';
import InfoBanner from 'merchant/views/Transactions/v1/B2bPayments/components/InfoBanner';
import ListFilter from 'merchant/views/Transactions/v1/B2bPayments/components/ListFilter';
import HeaderActions from 'merchant/views/Transactions/v1/BatchRefunds/HeaderActions';
import {
  trackFilterSubmit,
  trackSearchClicked,
  trackSearchClear,
  trackInvoiceUploadClick,
  trackInvoiceUploadStatus,
  trackInvoiceViewClick,
  trackInvoiceViewStatus,
  trackShown,
} from 'merchant/views/Transactions/v1/UploadInvoice/analytics';
import PaymentTable from 'merchant/views/Transactions/v1/UploadInvoice/components/PaymentTable';
import { isTransactionsV2Enabled } from 'merchant/views/Transactions/v2/common/utils';

const BulkUploadModal = lazy(() =>
  import(
    /* webpackChunkName: 'BulkUploadModal' */ 'merchant/views/Transactions/v1/UploadInvoice/BulkUpload'
  ),
);

class PaymentsListContainer extends ListContainer {
  getVersion = () => {
    const { splitz, user } = this.props;
    return isTransactionsV2Enabled(splitz, user) ? 'v2' : undefined;
  };

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
          version: this.getVersion(),
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
          version: this.getVersion(),
        });
      });
  };

  onSearchAnalytics = (params) => {
    trackSearchClicked({
      paymentId: params.id,
      paymentStatus: params.status,
      version: this.getVersion(),
    });
  };

  onClearAnalytics = () => {
    trackSearchClear({
      version: this.getVersion(),
    });
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
      version: this.getVersion(),
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
        version: this.getVersion(),
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
        version: this.getVersion(),
      });
    }
  };

  onView = async (id) => {
    const { viewInvoicePending, viewInvoiceSuccess, viewInvoiceError, showNotification } =
      this.props;
    viewInvoicePending({ id });
    trackInvoiceViewClick({
      documentId: id,
      version: this.getVersion(),
    });
    try {
      const response = await viewInvoice(id);
      if (response.data?.url) {
        window.open(response.data?.url, '_blank');
        trackInvoiceViewStatus({
          documentId: id,
          status: 'success',
          version: this.getVersion(),
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
        version: this.getVersion(),
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
    trackShown({
      version: this.getVersion(),
    });
  }

  render() {
    const { invoiceFetching, invoiceUploading, ...rest } = this.props;
    const { skip, count } = this.state;

    return (
      <ErrorBoundary resetOnProps rank={Ranks.P1} team={Teams.CROSS_BORDER}>
        <div className="content-wrapper">
          <HeaderActions>
            <div className="btn-toolbar pull-right">
              <a
                className="btn btn-link"
                href="https://razorpay.com/docs/payments/dashboard/upload-invoices/"
                target="_blank"
                rel="noopener noreferrer"
              >
                Guide to Upload Invoice <i className="i i-external-link" />
              </a>
              <Button.Primary onClick={this.onBulkUpload}>Bulk Upload</Button.Primary>
            </div>
          </HeaderActions>
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
