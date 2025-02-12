import React from 'react';
import { connect } from 'react-redux';

import BatchStats from 'common/ui/StatsTable';
import Time from 'common/ui/Time';
import { titleCase } from 'common/utils/rzp-utils';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { BatchUploadStatusLabel } from 'merchant/components/StatusLabel';
import BatchDetails from 'merchant/containers/BatchNew/Details';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import { fetchRouteBatchDetails as fetchBatchDetails } from 'merchant/reducers/batches';

const gaEvents = setGaTrack('Dashboard - Route - BU');

const downloadReportText =
  'Download the report containing Transfers, Reversals and Accounts data for this batch.';

function getStatsTable(stats) {
  return [
    [
      { title: 'Rows Processed', value: stats.totalCount },
      { title: 'Created', value: stats.successCount },
      { title: 'Failed', value: stats.failureCount },
    ],
  ];
}

function renderBatchDetails({ batch }) {
  const stats = getStatsTable({
    totalCount: batch.total_count,
    failureCount: batch.failure_count,
    successCount: batch.success_count,
  });

  return (
    <React.Fragment>
      <div className="equal-margin">
        <BatchStats stats={stats} />
      </div>

      <div className="equal-margin">
        <EntityDetailRow label="Batch Type" value={titleCase(batch.type)} />
        <EntityDetailRow label="Batch Name" value={batch.name} />
        <EntityDetailRow label="Status">
          <BatchUploadStatusLabel status={batch.status} />
          {batch.status === 'scheduled' && (
            <p>
              <em>
                Scheduled for <Time value={parseInt(batch.schedule_time / 1000)} format="lll" />
              </em>
            </p>
          )}
        </EntityDetailRow>
        <EntityDetailRow label="Created">
          <Time value={batch.created_at} />
        </EntityDetailRow>
      </div>
    </React.Fragment>
  );
}

class RouteBatchDetailsContainer extends React.Component {
  render() {
    return (
      <BatchDetails
        id={this.props.id}
        fetchBatchDetails={this.props.fetchBatchDetails}
        renderDetails={renderBatchDetails}
        gaEvents={gaEvents}
        downloadReportText={downloadReportText}
      />
    );
  }
}
export default connect(null, { fetchBatchDetails })(RouteBatchDetailsContainer);
