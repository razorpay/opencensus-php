import { Component, Fragment } from 'react';
import { connect } from 'react-redux';

import BatchDetails from 'merchant/containers/BatchNew/Details';
import { fetchLAReversalsBatchesDetails as fetchBatchDetails } from 'merchantLA/reducers/batches';
import setGaTrack from 'merchant/containers/BatchNew/ga';

import BatchStats from 'common/ui/StatsTable';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Time from 'common/ui/Time';
import { status } from 'common/ui/item/pair';
import { BatchUploadStatusLabel } from 'merchant/components/StatusLabel';

const gaEvents = setGaTrack('Dashboard - LA Reversals - BU');

const renderBatchDetails = props => {
  const { batch } = props;
  const statsTable = getStatsTable({
    batch_total: batch.success_count,
    issued_count: batch.total_count,
  });

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
        downloadReportText={
          'Download the report containing all Reversals data.'
        }
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
