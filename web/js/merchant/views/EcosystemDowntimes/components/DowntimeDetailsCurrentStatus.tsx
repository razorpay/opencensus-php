import React from 'react';
import type { DowntimeMetaDataType } from 'merchant/views/EcosystemDowntimes/types';
import { STATUS } from 'merchant/views/EcosystemDowntimes/constants';
import { Alert, Text } from '@razorpay/blade/components';
import moment from 'moment';

type DowntimeDetailsOverallStatusPropTypes = {
  activeDowntime: DowntimeMetaDataType | undefined;
  isMobile: boolean;
};

const DowntimeDetailsCurrentStatus = ({
  activeDowntime,
  isMobile,
}: DowntimeDetailsOverallStatusPropTypes): JSX.Element => {
  const statusInfo = STATUS[activeDowntime?.severity || 'operational'];
  const subText = activeDowntime
    ? `Started at: ${moment(activeDowntime.begin * 1000).format('DD MMM, HH:mm')}`
    : 'The instrument is operational';

  return (
    <div aria-label="downtime-details-current-status">
      <Alert
        description={<Text size={isMobile ? 'small' : 'medium'}>{subText}</Text>}
        intent={activeDowntime ? statusInfo.colorKey : 'positive'}
        isDismissible={false}
        title={activeDowntime ? `${statusInfo.text} Downtime` : 'Operational'}
        isFullWidth
      />
    </div>
  );
};

export default DowntimeDetailsCurrentStatus;
