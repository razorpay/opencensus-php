import { Component, Fragment } from 'react';
import { connect } from 'react-redux';

import BatchStats from 'common/ui/StatsTable';
import Time from 'common/ui/Time';
import { status } from 'common/ui/item/pair';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { BatchUploadStatusLabel } from 'merchant/components/StatusLabel';
import BatchDetails from 'merchant/containers/BatchNew/Details';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import { fetchLAReversalsBatchesDetails as fetchBatchDetails } from 'merchantLA/reducers/batches';

const gaEvents = setGaTrack('Dashboard - LA Reversals - BU');

const renderBatchDetails = (props) => {
  const { batch } = props;
  const statsTable = getStatsTable({
    batch_total: batch.success_count,
    issued_count: batch.total_count,
  });

  return (
    <Fragment>
      <div className="equal-margin">
        <BatchStats stats={statsTable} />
      </div>
      <div className="equal-margin">
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

class LARefundsBatchDetailsContainer extends Component {
  render() {
    return (
      <BatchDetails
        id={this.props.id}
        fetchBatchDetails={this.props.fetchBatchDetails}
        renderDetails={renderBatchDetails}
        gaEvents={gaEvents}
        downloadReportText="Download the report containing all Reversals data."
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

export default connect(null, {
  fetchBatchDetails,
})(LARefundsBatchDetailsContainer);
