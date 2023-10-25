import { Component, Fragment } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';

import Button from 'common/new-ui/Button';
import BatchStats from 'common/ui/StatsTable';
import DataTable from 'common/ui/Table/DataTable';
import Time from 'common/ui/Time';
import ListToggler from 'common/ui/Toggler/ListToggler';
import { amount, status } from 'common/ui/item/pair';
import { pluralize } from 'common/utils/rzp-utils';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { BatchUploadStatusLabel, InvoiceStatusLabel } from 'merchant/components/StatusLabel';
import BatchDetails from 'merchant/containers/BatchNew/Details';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import {
  fetchPaymentLinkBatchesDetails as fetchBatchDetails,
  cancelPaymentLinkBatch,
} from 'merchant/reducers/batches';
import { BATCH_TYPE } from 'merchant/views/PaymentPages/PaymentPages/constants';
import { showNotification } from 'merchant_common/reducers/notifications';
import { getStatsTableForBatchPLV2 } from 'merchant/views/PaymentLinks/utils';

import track from './track';

const gaEvents = setGaTrack('Dashboard - Payment Links - BU');

const paymentLinkEmail = {
  value: (paymentlink) =>
    paymentlink.customer_details ? paymentlink.customer_details.email : paymentlink.customer.email,
};

const renderBatchDetails = (props) => {
  const { batch, stats, paymentlinks } = props;
  const { type } = batch;
  const isBatchPaymentPage = type === BATCH_TYPE;
  const isBatchTypePaymentlinksV2 = type === 'payment_link_v2';
  const processedCount = batch ? batch.processed_count : null;
  let statsTable = getStatsTable(stats);

  if (isBatchPaymentPage) {
    statsTable = getStatsTableForBatchPLV2({
      stats,
      processedCount,
    });
  } else if (isBatchTypePaymentlinksV2) {
    statsTable = getStatsTableForPLV2(stats, processedCount);
  }

  const showCancelBtn = batch.status === 'partially_processed' || batch.status === 'processed';

  return (
    <Fragment>
      <div class="equal-margin">
        <BatchStats stats={statsTable} />
      </div>
      <div class="equal-margin">
        <EntityDetailRow label="Status">
          <BatchUploadStatusLabel status={batch.status} />

          {showCancelBtn && props.isBatchCancelEnabled && (
            <Button.Transparent class="Button--Link cancel-batch" onClick={props.onClickCancelBtn}>
              Cancel
            </Button.Transparent>
          )}
        </EntityDetailRow>
        <EntityDetailRow label="Created At">
          <Time value={batch.created_at} />
        </EntityDetailRow>
      </div>
      {!isBatchPaymentPage ? (
        <PaymentLinksTable
          totalItems={stats.issued_count}
          paymentlinks={paymentlinks}
          batchId={batch.id}
          isPaymentlinksV2Enabled={isBatchTypePaymentlinksV2}
        />
      ) : null}
      <hr />
      {stats.batch_total > stats.issued_count && batch.status !== 'created' && (
        <LinksErrMessage
          issuedCount={stats.issued_count}
          onDownload={props.onDownload}
          batchId={batch.id}
        />
      )}
    </Fragment>
  );
};

@connect(
  (state) => ({
    isBatchCancelEnabled: state.session.user.isBatchCancelEnabled,
  }),
  {
    cancelPaymentLinkBatch,
    showNotification,
    fetchBatchDetails,
  },
)
export default class PaymentLinksBatchDetailsContainer extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  componentDidMount() {
    track.onDetailsView();
  }

  componentWillUnmount() {
    track.onDetailViewUnMount();
  }

  onClickCancelBtn = () => {
    this.context.confirm({
      header: 'Cancel Batch?',
      message:
        'Cancelling this batch will also cancel any Payment Links created through this batch.',
      affirmativeLabel: 'Yes, Cancel',
      affirmativePendingLabel: 'Cancelling...',
      abortLabel: "No, don't!",
      action: () => {
        this.props
          .cancelPaymentLinkBatch(this.props.id)
          .then(() => {
            this.props.showNotification({
              type: 'success',
              message: 'This batch cancellation initiated.',
            });
          })
          .catch((err) => {
            this.props.showNotification({
              type: 'error',
              message: err.errors[0],
            });
          });
      },
    });
  };

  render() {
    return (
      <BatchDetails
        id={this.props.id}
        fetchBatchDetails={this.props.fetchBatchDetails}
        onClickCancelBtn={this.onClickCancelBtn}
        renderDetails={renderBatchDetails}
        gaEvents={gaEvents}
        isBatchCancelEnabled={this.props.isBatchCancelEnabled}
      />
    );
  }
}

function PaymentLinksTable({ isPaymentlinksV2Enabled, paymentlinks, batchId, totalItems }) {
  const newStatus = { title: 'Status', value: InvoiceStatusLabel };
  const statusLabel = isPaymentlinksV2Enabled ? newStatus : status;

  return (
    <ListToggler
      label={paymentlinks.length ? pluralize('Payment Link', paymentlinks.length) : ''}
      subLabel={paymentlinks.length ? 'created from this batch' : ''}
      limit={4}
      limitUrl={`/paymentlinks?batch_id=${batchId}`}
      totalItems={totalItems}
      onViewAllClick={() => {
        track.viewAllClick && track.viewAllClick();
        gaEvents.trackSeeAllLinks(batchId);
      }}
    >
      <DataTable
        columns={[paymentLinkEmail, amount, statusLabel]}
        customClass="invoice-list"
        limit={4}
        title="Payment Links"
        showHeaders={false}
        items={paymentlinks}
      />
    </ListToggler>
  );
}

function LinksErrMessage({ issuedCount, onDownload, batchId }) {
  return (
    <small class="help-block m-l">
      <i class="i i-info-circle" /> {issuedCount === 0 ? 'The payment' : 'Some'} links related to
      this batch were not created due to errors. Please
      <span class="btn-link" onClick={onDownload.bind(this, batchId)}>
        {' '}
        download{' '}
      </span>
      the report containing all Payment Links data
    </small>
  );
}

function getStatsTable(stats) {
  return [
    [
      { title: 'Total rows processed', value: stats.batch_total },
      { title: 'Payment links created', value: stats.issued_count },
    ],
    [
      {
        title: 'Paid',
        value: <span class="text-success">{stats.paid_count}</span>,
      },
      {
        title: 'Expired',
        value: <span class="text-danger">{stats.expired_count}</span>,
      },
    ],
  ];
}

function getStatsTableForPLV2(stats, processedCount) {
  return [
    [
      { title: 'Total rows processed', value: processedCount },
      { title: 'Payment links created', value: stats.created || 0 },
    ],
    [
      {
        title: 'Paid',
        value: <span class="text-success">{stats.paid || 0}</span>,
      },
      {
        title: 'Expired',
        value: <span class="text-danger">{stats.expired || 0}</span>,
      },
    ],
  ];
}
