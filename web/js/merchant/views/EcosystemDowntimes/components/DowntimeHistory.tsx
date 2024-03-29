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
    <DowntimeHistoryContainer aria-label="downtime-history" isMobile={isMobile}>
      <div className="previous-downtime-title">
        <Text weight="semibold">Past Downtimes</Text>
      </div>
      <Text size={isMobile ? 'small' : 'medium'} color="surface.text.gray.muted">
        Past 30 Days Incidents
      </Text>
      <div className="downtime-timeline" aria-label="downtime-history-data">
        {pastDowntimes.length > 0 ? (
          <Timeline data={pastDowntimes} />
        ) : (
          <Text size={isMobile ? 'small' : 'medium'} color="surface.text.gray.muted">
            No previous downtimes.
          </Text>
        )}
      </div>
    </DowntimeHistoryContainer>
  );
};

export default DowntimeHistory;
