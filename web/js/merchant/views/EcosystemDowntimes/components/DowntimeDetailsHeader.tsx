import React from 'react';
import type { InstrumentMetaData } from 'merchant/views/EcosystemDowntimes/types';
import { DowntimeDetailsHeaderStyled } from 'merchant/views/EcosystemDowntimes/styles';
import { Heading, Text } from '@razorpay/blade/components';
import {
  INSTRUMENT_TYPE_NAMES_MAP,
  METHOD_NAMES_MAP,
} from 'merchant/views/EcosystemDowntimes/constants';
import { toTitleCase } from '@razorpay/blade/utils';

type DowntimeDetailsHeaderType = {
  instrument: InstrumentMetaData;
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
        <Heading
          size="medium"
          type="normal"
          weight="bold"
          testID="downtime-details-header-instrumnt-name"
        >
          {name}
        </Heading>
        <div className="instrument-details" aria-label="downtime-details-header-subtext">
          <Text size={isMobile ? 'small' : 'medium'} type="subdued">
            {METHOD_NAMES_MAP[method]}
          </Text>
          <div className="separator" />
          <Text size={isMobile ? 'small' : 'medium'} type="subdued">
            {INSTRUMENT_TYPE_NAMES_MAP[group] || toTitleCase(group)}
          </Text>
        </div>
      </div>
    </DowntimeDetailsHeaderStyled>
  );
};

export default DowntimeDetailsHeader;
