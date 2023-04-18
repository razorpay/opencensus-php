import React from 'react';
import Timeline from './Timeline';
import { Text } from '@razorpay/blade/components';
import { DowntimeHistoryContainer } from 'merchant/views/EcosystemDowntimes/styles';

type DowntimeHistoryProps = {
  isMobile: boolean;
  pastDowntimes:
    | {
        icon?: JSX.Element;
        content: JSX.Element | string;
      }[]
    | undefined;
};

const DowntimeHistory = ({ isMobile, pastDowntimes = [] }: DowntimeHistoryProps): JSX.Element => {
  return (
    <DowntimeHistoryContainer aria-label="downtime-history">
      <div className="previous-downtime-title">
        <Text weight="bold">Past Downtimes</Text>
      </div>
      <Text size={isMobile ? 'small' : 'medium'} type="subdued">
        Past 30 Days Incidents
      </Text>
      <div className="downtime-timeline" aria-label="downtime-history-data">
        {pastDowntimes.length > 0 ? (
          <Timeline data={pastDowntimes} />
        ) : (
          <Text size={isMobile ? 'small' : 'medium'} type="subdued">
            No previous downtimes.
          </Text>
        )}
      </div>
    </DowntimeHistoryContainer>
  );
};

export default DowntimeHistory;
