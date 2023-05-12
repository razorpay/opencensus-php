import React from 'react';
import type {
  DowntimeMetaDataType,
  InstrumentMetaData,
} from 'merchant/views/EcosystemDowntimes/types';
import { METHOD_NAMES_MAP, STATUS } from 'merchant/views/EcosystemDowntimes/constants';
import { Alert, Text } from '@razorpay/blade/components';
import moment from 'moment';
import { toTitleCase } from '@razorpay/blade/utils';

type DowntimeDetailsOverallStatusPropTypes = {
  instrument: InstrumentMetaData;
  activeDowntime: DowntimeMetaDataType | undefined;
  isMobile: boolean;
};

const DowntimeDetailsCurrentStatus = ({
  instrument,
  activeDowntime,
  isMobile,
}: DowntimeDetailsOverallStatusPropTypes): JSX.Element => {
  const { name, method } = instrument;
  const statusInfo = STATUS[activeDowntime?.severity || 'operational'];
  const subText = activeDowntime
    ? `Started at: ${moment(activeDowntime.begin * 1000).format('DD MMM, HH:mm')}`
    : `${name} (${METHOD_NAMES_MAP?.[method] || toTitleCase(method)}) is operational`;

  return (
    <div aria-label="downtime-details-current-status">
      <Alert
        description={
          <Text size={isMobile ? 'small' : 'medium'} testID="status-description-text">
            {subText}
          </Text>
        }
        intent={activeDowntime ? statusInfo.colorKey : 'positive'}
        isDismissible={false}
        title={activeDowntime ? `${statusInfo.text} Downtime` : 'Operational'}
        isFullWidth
      />
    </div>
  );
};

export default DowntimeDetailsCurrentStatus;
