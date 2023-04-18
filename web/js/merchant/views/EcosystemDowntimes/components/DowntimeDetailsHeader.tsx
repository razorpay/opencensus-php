import React from 'react';
import type { FocusedInstrumentMetaDataType } from 'merchant/views/EcosystemDowntimes/types';
import { DowntimeDetailsHeaderStyled } from 'merchant/views/EcosystemDowntimes/styles';
import { Heading, Text } from '@razorpay/blade/components';
import { METHOD_NAMES_MAP } from 'merchant/views/EcosystemDowntimes/constants';
import { humanize } from 'common/utils/rzp-utils';

type DowntimeDetailsHeaderType = {
  instrument: FocusedInstrumentMetaDataType;
  isMobile: boolean;
};

const DowntimeDetailsHeader = ({
  instrument,
  isMobile,
}: DowntimeDetailsHeaderType): JSX.Element => {
  const { name, method, logo, group } = instrument;
  return (
    <DowntimeDetailsHeaderStyled aria-label="ecosystem-downtime-details-header">
      <div className="instrument-logo">
        <img src={logo} height={30} width={30} />
      </div>
      <div className="instrument-name">
        <Heading size="medium" type="normal" weight="bold">
          {name}
        </Heading>
        <div className="instrument-details">
          <Text size={isMobile ? 'small' : 'medium'} type="subdued">
            {METHOD_NAMES_MAP[method]}
          </Text>
          <div className="separator" />
          <Text size={isMobile ? 'small' : 'medium'} type="subdued">
            {humanize(group)}
          </Text>
        </div>
      </div>
    </DowntimeDetailsHeaderStyled>
  );
};

export default DowntimeDetailsHeader;
