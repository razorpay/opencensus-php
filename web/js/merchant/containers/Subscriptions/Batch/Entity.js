import { Component, Fragment } from 'react';
import { connect } from 'react-redux';

import BatchDetails from 'merchant/containers/BatchNew/Details';
import Time from 'rzp/ui/Time';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import BatchStats from 'rzp/ui/StatsTable';

import setGaTrack from 'merchant/containers/BatchNew/ga';

import { fetchHostedMandateBatchDetails as fetchBatchDetails } from 'merchant/modules/batches';
import { BatchUploadStatusLabel } from 'merchant/components/StatusLabel';
import { titleCase } from 'rzp/utils/rzp-utils';

const gaEvents = setGaTrack('Dashboard - Subscriptions - BU');

const renderBatchDetails = ({ batch }) => (
  <Fragment>
    <div class="equal-margin">
      <BatchStats
        stats={getStatsTable({
          totalCount: batch.total_count,
          failureCount: batch.failure_count,
          successCount: batch.success_count,
        })}
      />
    </div>
    <div class="equal-margin">
      <EntityDetailRow label="Batch Type" value={titleCase(batch.type)} />
      <EntityDetailRow label="Batch Name" value={batch.name} />
      <EntityDetailRow label="Status">
        <BatchUploadStatusLabel status={batch.status} />
      </EntityDetailRow>
      <EntityDetailRow label="Status">
        <Time value={batch.created_at} />
      </EntityDetailRow>
    </div>
  </Fragment>
);

@connect(null, { fetchBatchDetails })
export default class AuthLinksBatchEntityContainer extends Component {
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
  return [
    [
      { title: 'Total Rows', value: stats.totalCount },
      { title: 'Payments Created', value: stats.successCount },
      { title: 'Rows Failed', value: stats.failureCount },
    ],
  ];
}
