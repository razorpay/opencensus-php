import { connect } from 'react-redux';

import Time from 'common/ui/Time';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import BatchStats from 'common/ui/StatsTable';

import setGaTrack from 'merchant/containers/BatchNew/ga';
import BatchDetails from 'merchant/containers/BatchNew/Details';
import { fetchRouteBatchDetails as fetchBatchDetails } from 'merchant/reducers/batches';
import { BatchUploadStatusLabel } from 'merchant/components/StatusLabel';
import { titleCase } from 'common/utils/rzp-utils';

const gaEvents = setGaTrack('Dashboard - Route - BU');

const downloadReportText = 'Download the report containing Transfers, Reversals and Accounts data for this batch.'

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
      <div class="equal-margin">
        <BatchStats stats={stats} />
      </div>

      <div class="equal-margin">
        <EntityDetailRow label="Batch Type" value={titleCase(batch.type)} />
        <EntityDetailRow label="Batch Name" value={batch.name} />
        <EntityDetailRow label="Status">
          <BatchUploadStatusLabel status={batch.status} />
          {batch.status === 'scheduled' && (
            <p>
              <em>
                Scheduled for{' '}
                <Time
                  value={parseInt(batch.schedule_time / 1000)}
                  format="lll"
                />
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

@connect(null, { fetchBatchDetails })
export default class RouteBatchDetailsContainer extends React.Component {
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