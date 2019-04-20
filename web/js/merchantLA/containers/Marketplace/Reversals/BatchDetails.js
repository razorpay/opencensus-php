import { Component, Fragment } from 'react';
import { connect } from 'react-redux';

import BatchDetails from 'merchant/containers/BatchNew/Details';
import { fetchLAReversalsBatchesDetails as fetchBatchDetails } from 'merchantLA/modules/batches';
import { pluralize } from 'rzp/utils/rzp-utils';
import setGaTrack from 'merchant/containers/BatchNew/ga';

import BatchStats from 'ui/StatsTable';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Time from 'rzp/ui/Time';
import { status } from 'rzp/ui/item/pair';
import { BatchUploadStatusLabel } from 'merchant/components/StatusLabel';

const gaEvents = setGaTrack('Dashboard - Payment Links - BU');

const renderBatchDetails = props => {
  const { batch = {}, stats = {} } = props;
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
        <EntityDetailRow label="Created At">
          <Time value={batch.created_at} />
        </EntityDetailRow>
      </div>
    </Fragment>
  );
};

@connect(null, {
  fetchBatchDetails,
})
export default class LARefundsBatchDetailsContainer extends Component {
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

function getStatsTable(stats) {
  if (status)
    return [
      [
        { title: 'Total rows processed', value: stats.batch_total || '--' },
        { title: 'Reversals created', value: stats.issued_count || '--' },
      ],
    ];
}
