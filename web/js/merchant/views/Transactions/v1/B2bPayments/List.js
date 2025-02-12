import React, { lazy } from 'react';
import { Alert } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import ErrorBoundary, { Teams, Ranks } from 'common/new-ui/ErrorBoundary';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { withSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import ListContainer from 'merchant/containers/ListContainer';
import { b2bActions } from 'merchant/reducers/b2bExports';
import { fetchB2bPayments } from 'merchant/reducers/collection';
import {
  trackFilterSubmit,
  trackSearchClicked,
  trackSearchClear,
  trackInvoiceUploadClick,
  trackInvoiceUploadStatus,
  trackInvoiceViewClick,
  trackInvoiceViewStatus,
  trackShown,
} from 'merchant/views/Transactions/v1/B2bPayments/analytics';
import EmptyComponent from 'merchant/views/Transactions/v1/B2bPayments/components/EmptyComponent';
import ListFilter from 'merchant/views/Transactions/v1/B2bPayments/components/ListFilter';
import ListTable from 'merchant/views/Transactions/v1/B2bPayments/components/ListTable';
import HeaderActions from 'merchant/views/Transactions/v1/BatchRefunds/HeaderActions';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

// Lazy loaded components
const BuyerAddressModalLazy = lazy(() =>
  import(
    /* webpackChunkName: 'BuyerAddressModal' */ 'merchant/views/Transactions/v1/B2bPayments/components/BuyerAddressModal'
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
          version: 'v2',
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
          version: 'v2',
        });
      });
  };

  onSearchAnalytics = (params) => {
    trackSearchClicked({
      paymentId: params.id,
      paymentStatus: params.status,
      version: 'v2',
    });
  };

  onClearAnalytics = () => {
    trackSearchClear({
      version: 'v2',
    });
  };

  onUploadInvoice = async (id, file) => {
    const { uploadInvoicePending, uploadInvoiceSuccess, uploadInvoiceError, showNotification } =
      this.props;
    uploadInvoicePending({ id });
    trackInvoiceUploadClick({
      paymentId: id,
      version: 'v2',
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
        version: 'v2',
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
        version: 'v2',
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
      version: 'v2',
    });
    try {
      const response = await b2bActions.getInvoiceDetails(id);
      if (response.data?.url) {
        window.open(response.data?.url, '_blank');
        trackInvoiceViewStatus({
          documentId: id,
          status: 'success',
          version: 'v2',
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
        version: 'v2',
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
    trackShown({
      version: 'v2',
    });
  }

  render() {
    const { data, isLoading, invoiceFetching, invoicesUploading, splitz } = this.props;
    const { skip, count } = this.state;

    const { abExperiments } = splitz || {
      abExperiments: { UploadInvoiceSenderAddr: undefined },
    };
    const isSenderDetailsEnabled = abExperiments?.UploadInvoiceSenderAddr
      ? isExperimentEnabled(abExperiments.UploadInvoiceSenderAddr)
      : false;

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
                Guide to Upload Invoice &nbsp;
                <i className="i i-external-link" />
              </a>
            </div>
          </HeaderActions>
          <ListFilter
            form="b2bPaymentListFilter"
            count={count}
            onSubmit={this.onFilterSubmit}
            onSearchAnalytics={this.onSearchAnalytics}
            onClearAnalytics={this.onClearAnalytics}
          />
          <Alert
            description="Uploading an invoice for international payments received via ACH/SWIFT/SEPA/BACS or domestic payments for international businesses is required for audit purposes as per RBI guidelines. Without a valid invoice for such transactions, your settlements cannot be processed and will be put on hold."
            isDismissible={false}
            isFullWidth
            marginBottom="spacing.5"
            color="information"
          />
          <ListTable
            {...this.props}
            count={count}
            skip={skip}
            items={data.items}
            loading={isLoading}
            uploadState={invoicesUploading}
            invoiceFetching={invoiceFetching}
            isSenderDetailsEnabled={isSenderDetailsEnabled}
            EmptyComponent={EmptyComponent}
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

const mapStatesToProps = (state) => ({ ...state.b2bExportsTransactions, user: state.session.user });

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

export default withSplitzService(
  connect(mapStatesToProps, mapDispatchToProps)(withRouter(PaymentsListContainer)),
);
