import { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import BatchDetails from 'merchant/containers/BatchNew/Details';
import { fetchPaymentLinkBatchesDetails as fetchBatchDetails } from 'merchant/modules/batches';
import { pluralize } from 'rzp/utils/rzp-utils';
import setGaTrack from 'merchant/containers/BatchNew/ga';

import BatchStats from 'merchant/components/BatchNew/Stats';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import EmptyTableRow from 'rzp/ui/EmptyTableRow';
import DataTable from 'rzp/ui/Table/DataTable';
import ListToggler from 'rzp/ui/Toggler/ListToggler';
import Time from 'rzp/ui/Time';
import { amount, status } from 'rzp/ui/item/pair';
import { BatchUploadStatusLabel } from 'merchant/components/StatusLabel';

const gaEvents = setGaTrack('Dashboard - Payment Links - BU');

const renderBatchDetails = props => {
  const { batch, stats, invoices } = props;
  const statsTable = getStatsTable(stats);
  return (
    <Fragment>
      <div class="equal-margin">
        <BatchStats stats={statsTable} />
      </div>
      <div class="equal-margin">
        <EntityDetailRow label="Status">
          <BatchUploadStatusLabel status={batch.status} />
        </EntityDetailRow>
        <EntityDetailRow label="Status">
          <Time value={batch.created_at} />
        </EntityDetailRow>
      </div>
      <InvoicesTable
        totalItems={stats.issued_count}
        invoices={invoices}
        batchId={batch.id}
      />
      <hr />
      {stats.batch_total > stats.issued_count &&
        batch.status !== 'created' && (
          <LinksErrMessage
            issuedCount={stats.issued_count}
            onDownload={props.onDownload}
            batchId={batch.id}
          />
        )}
    </Fragment>
  );
};

@connect(null, {
  fetchBatchDetails,
})
export default class PaymentLinksBatchDetailsContainer extends Component {
  render() {
    return (
      <BatchDetails
        id={this.props.id}
        fetchBatchDetails={this.props.fetchBatchDetails}
        renderDetails={renderBatchDetails}
        gaEvents={gaEvents}
      />
    );
  }
}

function InvoicesTable({ invoices, batchId, totalItems }) {
  return (
    <ListToggler
      label={invoices.length ? pluralize('Payment Link', invoices.length) : ''}
      subLabel={invoices.length ? 'created from this batch' : ''}
      limit={4}
      limitUrl={`/paymentlinks?batch_id=${batchId}`}
      totalItems={totalItems}
      onViewAllClick={gaEvents.trackSeeAllLinks(batchId)}
    >
      <DataTable
        columns={[invoiceEmail, amount, status]}
        customClass="invoice-list"
        limit={4}
        title="Invoices"
        showHeaders={false}
        items={invoices}
      />
    </ListToggler>
  );
}

function LinksErrMessage({ issuedCount, onDownload, batchId }) {
  return (
    <small class="help-block m-l">
      <i class="i i-info-circle" /> {issuedCount === 0 ? 'The payment' : 'Some'}{' '}
      links related to this batch were not created due to errors. Please<span
        class="btn-link"
        onClick={onDownload.bind(this, batchId)}
      >
        {' '}
        download{' '}
      </span>the report containing all Payment Links data
    </small>
  );
}

var invoiceEmail = {
  value: invoice => invoice.customer_details.email,
};

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
