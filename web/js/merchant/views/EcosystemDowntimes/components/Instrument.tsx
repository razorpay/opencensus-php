import React from 'react';
import { STATUS } from 'merchant/views/EcosystemDowntimes/constants';
import {
  instrumentClick,
  trackEcosystemDowntimeEvents,
} from 'merchant/views/EcosystemDowntimes/events';
import { InstrumentItem } from 'merchant/views/EcosystemDowntimes/styles';
import { Text } from '@razorpay/blade/components';
import type { InstrumentType } from 'merchant/views/EcosystemDowntimes/types';

const Instrument = ({ instrument, status, onClick }: InstrumentType): JSX.Element => {
  const severity = status?.severity;
  const downtimeType = severity ? STATUS?.[severity] : STATUS.operational;

  const handleOnClick = () => {
    trackEcosystemDowntimeEvents(instrumentClick(instrument));
    onClick?.(instrument);
  };

  const { logo, name } = instrument;
  const { text, icon } = downtimeType;

  return (
    <InstrumentItem aria-label="instrument" status={downtimeType} onClick={handleOnClick}>
      <img className="instrument-logo" src={logo} alt={name} />
      <Text
        contrast="low"
        size="medium"
        truncateAfterLines={1}
        type="normal"
        variant="body"
        weight="regular"
      >
        {name}
      </Text>
      <div className="instrument-status" aria-label="status" title={text}>
        {icon}
      </div>
    </InstrumentItem>
  );
};

export default Instrument;
