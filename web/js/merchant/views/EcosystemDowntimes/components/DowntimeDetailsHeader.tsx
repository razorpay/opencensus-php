import React from 'react';
import { Heading, Text } from '@razorpay/blade/components';

import { toTitleCase } from 'common/utils';
import {
  INSTRUMENT_TYPE_NAMES_MAP,
  METHOD_NAMES_MAP,
} from 'merchant/views/EcosystemDowntimes/constants';
import { DowntimeDetailsHeaderStyled } from 'merchant/views/EcosystemDowntimes/styles';

import type { InstrumentMetaData } from 'merchant/views/EcosystemDowntimes/types';

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
          weight="semibold"
          testID="downtime-details-header-instrumnt-name"
          size="small"
          color="surface.text.gray.normal"
        >
          {name}
        </Heading>
        <div className="instrument-details" aria-label="downtime-details-header-subtext">
          <Text size={isMobile ? 'small' : 'medium'} color="surface.text.gray.muted">
            {METHOD_NAMES_MAP[method]}
          </Text>
          <div className="separator" />
          <Text size={isMobile ? 'small' : 'medium'} color="surface.text.gray.muted">
            {INSTRUMENT_TYPE_NAMES_MAP[group] || toTitleCase(group)}
          </Text>
        </div>
      </div>
    </DowntimeDetailsHeaderStyled>
  );
};

export default DowntimeDetailsHeader;
