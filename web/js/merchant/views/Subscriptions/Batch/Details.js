import React from 'react';
import { connect } from 'react-redux';

import BatchStats from 'common/ui/StatsTable';
import Time from 'common/ui/Time';
import { titleCase } from 'common/utils/rzp-utils';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { BatchUploadStatusLabel } from 'merchant/components/StatusLabel';
import BatchDetails from 'merchant/containers/BatchNew/Details';
import setGaTrack from 'merchant/containers/BatchNew/ga';
import { fetchHostedMandateBatchDetails as fetchBatchDetails } from 'merchant/reducers/batches';

const gaEvents = setGaTrack('Dashboard - Subscriptions - BU');

function renderBatchDetails({ batch }) {
  const stats = getStatsTable({
    totalCount: batch.total_count,
    failureCount: batch.failure_count,
    successCount: batch.success_count,
  });

  return (
    <>
      <div className="equal-margin">
        <BatchStats stats={stats} />
      </div>

      <div className="equal-margin">
        <EntityDetailRow label="Batch Type" value={getBatchType(batch)} />
        <EntityDetailRow label="Batch Name" value={batch.name} />
        <EntityDetailRow label="Status">
          <BatchUploadStatusLabel status={batch.status} />
          {batch.status === 'scheduled' && (
            <p>
              <em>
                Scheduled for <Time value={parseInt(batch.schedule_time / 1000, 10)} format="lll" />
              </em>
            </p>
          )}
        </EntityDetailRow>
        <EntityDetailRow label="Created">
          <Time value={batch.created_at} />
        </EntityDetailRow>
      </div>
    </>
  );
}

class RegistrationLinksBatchDetailsContainer extends React.Component {
  render() {
    return (
      <BatchDetails
        id={this.props.id}
        fetchBatchDetails={this.props.fetchBatchDetails}
        renderDetails={renderBatchDetails}
        gaEvents={gaEvents}
        downloadReportText={getDownloadReportText}
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

function getDownloadReportText(props) {
  const { type } = props.batch || {};

  return `Download the report containing all ${
    type === 'auth_link' ? 'Registration' : 'Recurring Debit'
  } Links data.`;
}

function getBatchType(batch) {
  const { type } = batch;

  let linkType = type;
  if (type.includes('auth')) {
    linkType = type.replace('auth', 'registration');
  }
  if (type.includes('recurring_charge_bulk')) {
    linkType = type.replace('recurring_charge_bulk', 'recurring_charge');
  }

  return titleCase(linkType);
}

export default connect(null, { fetchBatchDetails })(RegistrationLinksBatchDetailsContainer);
