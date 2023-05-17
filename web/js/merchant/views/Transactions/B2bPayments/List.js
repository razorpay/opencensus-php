import React, { lazy } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

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
} from 'merchant/views/Transactions/B2bPayments/analytics';

// components
// eslint-disable-next-line no-restricted-imports
import HeaderAction from 'common/ui/HeaderAction';
import ListContainer from 'merchant/containers/ListContainer';
import ListTable from 'merchant/views/Transactions/B2bPayments/components/ListTable';
import ListFilter from 'merchant/views/Transactions/B2bPayments/components/ListFilter';
import EmptyComponent from 'merchant/views/Transactions/B2bPayments/components/EmptyComponent';
import InfoBanner from 'merchant/views/Transactions/B2bPayments/components/InfoBanner';
import ErrorBoundary, { Teams, Ranks } from 'common/new-ui/ErrorBoundary';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

// actions
import { showNotification } from 'merchant_common/reducers/notifications';
import { b2bActions } from 'merchant/reducers/b2bExports';
import { fetchB2bPayments } from 'merchant/reducers/collection';
import { closeModal, openModal } from 'merchant_common/reducers/modals';

// Lazy loaded components
const BuyerAddressModalLazy = lazy(() =>
  import(
    /* webpackChunkName: 'BuyerAddressModal' */ 'merchant/views/Transactions/B2bPayments/components/BuyerAddressModal'
  ),
);
///- Lazy loaded components

class PaymentsListContainer extends ListContainer {
  onFilterSubmit = (params) => {
    this.search(params)
      .then(() => {
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

  onUploadInvoice = async (id, file) => {
    const { uploadInvoicePending, uploadInvoiceSuccess, uploadInvoiceError, showNotification } =
      this.props;
    uploadInvoicePending({ id });
    trackInvoiceUploadClick({
      paymentId: id,
    });
    try {
      await b2bActions.uploadInvoice(id, file);
      uploadInvoiceSuccess({ id });
      this.paginate({});
      showNotification({
        type: 'success',
        message: 'File uploaded successfully',
      });
      trackInvoiceUploadStatus({
        paymentId: id,
        status: 'success',
      });
    } catch (err) {
      uploadInvoiceError({ id, error: err });
      showNotification({
        type: 'error',
        message: 'Failed to upload file. Please try again!',
      });
      trackInvoiceUploadStatus({
        paymentId: id,
        status: 'failure',
      });
    }
  };

  onView = async (id) => {
    const {
      getInvoiceDetailsPending,
      getInvoiceDetailsSuccess,
      getInvoiceDetailsError,
      showNotification,
    } = this.props;
    getInvoiceDetailsPending({ id });
    trackInvoiceViewClick({
      documentId: id,
    });
    try {
      const response = await b2bActions.getInvoiceDetails(id);
      if (response.data?.url) {
        window.open(response.data?.url, '_blank');
        trackInvoiceViewStatus({
          documentId: id,
          status: 'success',
        });
        getInvoiceDetailsSuccess({ id });
      }
    } catch (err) {
      const message = Array.isArray(err?.errors)
        ? err?.errors.join(' ')
        : 'Failed to fetch invoice details. Please try again!';

      getInvoiceDetailsError({ id });
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

  openBuyerAddressModal = (paymentId) => {
    const { openModal, closeModal, showNotification } = this.props;
    openModal({
      component: (
        <SuspenseWithLoader>
          <BuyerAddressModalLazy
            paymentId={paymentId}
            onClose={closeModal}
            showNotification={showNotification}
          />
        </SuspenseWithLoader>
      ),
      size: 'xlarge',
    });
  };

  componentDidMount() {
    trackShown();
  }

  render() {
    const { data, isLoading, invoiceFetching, invoicesUploading } = this.props;
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
                Guide to Upload Invoice &nbsp;
                <i className="i i-external-link" />
              </a>
            </div>
          </HeaderAction>
          <ListFilter
            form="b2bPaymentListFilter"
            count={count}
            onSubmit={this.onFilterSubmit}
            onSearchAnalytics={this.onSearchAnalytics}
            onClearAnalytics={this.onClearAnalytics}
          />
          <InfoBanner text="Uploading an invoice for international payments is required for audit purposes as per RBI guidelines. Without a valid invoice for each transaction, your settlements cannot be processed and will be put on hold." />
          <ListTable
            {...this.props}
            count={count}
            skip={skip}
            items={data.items}
            loading={isLoading}
            EmptyComponent={EmptyComponent}
            uploadState={invoicesUploading}
            invoiceFetching={invoiceFetching}
            paginate={this.paginate}
            onView={this.onView}
            onUpload={this.onUploadInvoice}
            onBuyerAddressClick={this.openBuyerAddressModal}
          />
        </div>
      </ErrorBoundary>
    );
  }
}

const mapStatesToProps = (state) => state.b2bExportsTransactions;

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      showNotification,
      fetchAll: fetchB2bPayments,
      uploadInvoiceError: b2bActions.uploadInvoiceError,
      uploadInvoiceSuccess: b2bActions.uploadInvoiceSuccess,
      uploadInvoicePending: b2bActions.uploadInvoicePending,
      getInvoiceDetailsPending: b2bActions.getInvoiceDetailsPending,
      getInvoiceDetailsError: b2bActions.getInvoiceDetailsError,
      getInvoiceDetailsSuccess: b2bActions.getInvoiceDetailsSuccess,
      openModal,
      closeModal,
    },
    dispatch,
  );
};

export default connect(mapStatesToProps, mapDispatchToProps)(PaymentsListContainer);
