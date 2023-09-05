import { Component } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import DataTable from 'common/ui/Table/DataTable';
import { batchId, totalCount, status, createdAt } from 'common/ui/item/pair';
import { titleCase } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { getCustomURL } from 'merchant/components/DocsLink';
import ShowWhen from 'merchant/components/ShowWhen';
import BatchUpload from 'merchant/containers/BatchNew/Upload';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import {
  batchDownload,
  cancelBatchRefund as fnCancelBatchRefund,
  fetchBatchAjax,
  updateBatchInList,
} from 'merchant/reducers/batches';
import store from 'merchant/store';
import HeaderActions from 'merchant/views/Transactions/v1/BatchRefunds/HeaderActions';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

import BatchListFilter from './BatchListFilter';

const gaEvents = setGaTrack('Dashboard - Instant Refunds - BU');

export const STATUS_VALUES = ['created'];

const batchName = {
  title: 'Name',
  value: ({ name }) => name,
};

function batchActions({
  viewAll,
  issueAll,
  batchType,
  onDownloadClick,
  issuableIdList,
  CancelBatchRefund,
  openModal,
  closeModal,
}) {
  const storeData = store.getState();
  const plType = storeData.session.user.isPaymentlinksV2Enabled
    ? 'payment_link_v2'
    : 'payment_link';
  return {
    viewAll,
    issueAll,
    title: 'Actions',
    value: (item) => (
      <div class="btn-toolbar">
        {STATUS_VALUES.includes(item.status) && (
          <button
            class="btn btn-xs btn-default btn-outline cancel-batch-btn"
            onClick={() => {
              window.rzpAnalytics?.({
                eventCategory: `Batch ${titleCase(batchType)}`,
                eventAction: 'Cancel - List view',
                eventLabel: `Click to upload file`,
              });
              cancelBatch({
                batch: item,
                openModal,
                batchType,
                closeModal,
                CancelBatchRefund,
              });
            }}
          >
            Cancel
          </button>
        )}
        <button class="btn btn-xs btn-default" onClick={() => onDownloadClick(item.id)}>
          Download
        </button>
        {item.status === 'processed' &&
          handleLinks({ viewAll, issueAll, item, plType, issuableIdList })}
      </div>
    ),
  };
}

function handleLinks({ viewAll, issueAll, item, plType, issuableIdList }) {
  let elem = null;

  if (item.type === plType) {
    if (viewAll) {
      elem = (
        <button class="btn btn-default btn-xs" onClick={(_) => viewAll(item)}>
          view all links
        </button>
      );
    }

    if (
      issueAll &&
      item.status === 'processed' &&
      (!issuableIdList || issuableIdList.indexOf(item.id) > -1)
    ) {
      elem = (
        <button class="btn btn-default btn-xs" onClick={(_) => issueAll(item)}>
          Issue all links
        </button>
      );
    }
  }

  return elem;
}

function cancelBatch({ batch, openModal, closeModal, CancelBatchRefund }) {
  openModal({
    size: 'small',
    component: (
      <ConnectedCancelConfirmation
        cancelBatchRefund={CancelBatchRefund}
        closeModal={closeModal}
        batch={batch}
      />
    ),
  });
}

class BatchList extends Component {
  dowload = (id) => {
    const windowRef = window.open('', '_blank');
    this.props
      .batchDownload(id)
      .then((response) => {
        windowRef.location.href = response.data.url;
      })
      .catch(({ errors }) => {
        windowRef.close();
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  openBatchUploadModal = () => {
    const { version } = this.props;
    selfServeTrackInitiate({
      selfServeAction: 'Batch refund File Uploaded',
      page: 'Batch Refunds',
      screen: 'Transactions',
      version,
    });
    this.props.openModal({
      size: 'large',
      component: (
        <BatchUpload
          docUrl={getCustomURL('https://razorpay.com/docs/payments/refunds/batch/')}
          sampleUrl={this.props.sampleUrl}
          closeUrl="/refunds/batchuploads"
          ctaText="Create Batch"
          pendingText="Creating & Sending..."
          batchType="refund"
          maxRows={5000}
          createBatch={this.props.createRefundBatch}
          validateBatch={this.props.validateRefundBatch}
          gaEvents={gaEvents}
        />
      ),
    });
    window.rzpAnalytics?.({
      eventCategory: 'Batch Refund',
      eventAction: 'Click to upload - List view',
      eventLabel: `Click to upload`,
    });
  };

  render() {
    const {
      mode,
      docUrl,
      count,
      skip,
      paginate,
      onSubmit,
      CancelBatchRefund,
      viewAll,
      issueAll,
      issuableIdList,
      showBatchName,
      session,
      batchType,
      openModal,
      sampleUrl,
      closeModal,
      gaEvents,
    } = this.props;
    const { user } = session;
    const handleDownloadClick = this.dowload;
    const items = this.props.items;

    const headerActionChildren = (
      <div class="btn-toolbar pull-right">
        {sampleUrl && (
          <a
            class="btn btn-link"
            href={sampleUrl}
            onClick={gaEvents.trackSampleFileDownload('From List View')}
            role="link"
          >
            Download Sample File
          </a>
        )}
        <ShowWhen additionalCondition={(usr) => usr.isOrgAllowedFunctionality('external_links')}>
          {docUrl && (
            <a
              class="btn btn-link"
              href={docUrl}
              target="_blank"
              rel="noopener noreferrer"
              role="link"
            >
              Documentation &nbsp;
              <i class="i i-external-link" />
            </a>
          )}
        </ShowWhen>

        {(session.mode !== 'live' || !user.isRejected) && (
          /* To make the CTAs on header to be sticky in teh bottom need to add a wrapper to them added same */
          <span className="cta-container">
            <button class="btn btn-primary pull-right" onClick={this.openBatchUploadModal}>
              Click here to upload
            </button>
          </span>
        )}
      </div>
    );

    return (
      <div class="content-wrapper" data-testid="batchrefunds-batchlist">
        {/* passing the new props to the HeaderAction component to support the m-web view */}
        <HeaderActions>{headerActionChildren}</HeaderActions>

        <BatchListFilter form="batchListFilter" count={count} onSubmit={onSubmit} />
        <DataTable
          title="Batch Uploads"
          columns={[
            batchId,
            ...(showBatchName ? [batchName] : []),
            totalCount,
            status,
            createdAt,
            batchActions({
              mode,
              viewAll,
              batchType,
              issueAll,
              onDownloadClick: handleDownloadClick,
              issuableIdList,
              openModal,
              CancelBatchRefund,
              closeModal,
            }),
          ]}
          count={count}
          skip={skip}
          paginate={paginate}
          {...this.props}
          items={items}
        />
      </div>
    );
  }
}

export default connect(
  (state) => ({ session: state.session }),
  (dispatch) =>
    bindActionCreators(
      {
        batchDownload,
        CancelBatchRefund: fnCancelBatchRefund,
        ...NotificationsActions,
        ...ModalActions,
      },
      dispatch,
    ),
)(BatchList);

class CancelConfirmation extends Component {
  state = {
    loading: false,
  };
  cancel = () => {
    this.setState({ loading: true });
    window.rzpAnalytics?.({
      eventCategory: `Batch ${titleCase(this.props.batch.type)}`,
      eventAction: 'Yes cancel - Cancel Modal',
      eventLabel: this.props.batch.status,
    });
    fetchBatchAjax(this.props.batch.id).then((r) => {
      const batch = r.batch;
      if (batch.status == 'created') {
        return this.props.cancelBatchRefund(this.props.batch.id).then(() => {
          this.setState({ loading: false });
          this.props.closeModal();
          this.props.showNotification({
            type: 'success',
            message: 'This batch cancellation initiated.',
          });
        });
      } else {
        updateBatchInList(this.props.batch);
        this.setState({ loading: false });
        this.props.closeModal();
        let message;
        if (batch.status == 'processing') {
          message = 'Batch refund is already being processed.';
        }
        if (batch.status == 'cancelled') {
          message = 'Batch refund already cancelled.';
        }
        if (batch.status == 'processed') {
          message = 'Batch refund already processed.';
        }
        if (message) {
          this.props.showNotification({
            type: 'error',
            message,
          });
        }
      }

      return true;
    });
  };
  render() {
    return (
      <div class="batch-cancel-confirmation">
        <div class="content-header">Are you sure you want to cancel the batch file?</div>
        {/* <div class="content-sub-header">
          Many of the refunds might have been processed already
        </div> */}
        <div class="content">
          <button class="btn btn-default" onClick={this.props.closeModal}>
            No don't
          </button>
          <button disabled={this.state.loading} onClick={this.cancel} class="btn btn-primary">
            {this.state.loading ? <span>Please Wait..</span> : <span>Yes, cancel</span>}
          </button>
        </div>
      </div>
    );
  }
}

export const ConnectedCancelConfirmation = connect(null, (dispatch) =>
  bindActionCreators({ ...NotificationsActions }, dispatch),
)(CancelConfirmation);
