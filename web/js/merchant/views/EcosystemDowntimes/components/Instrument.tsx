import React from 'react';
import { STATUS } from 'merchant/views/EcosystemDowntimes/constants';
import {
  instrumentClick,
  trackEcosystemDowntimeEvents,
} from 'merchant/views/EcosystemDowntimes/events';
import { InstrumentItem } from 'merchant/views/EcosystemDowntimes/styles';
import { Text } from '@razorpay/blade/components';
import type {
  DowntimeMetaDataType,
  InstrumentMetaData,
} from 'merchant/views/EcosystemDowntimes/types';

type InstrumentType = {
  instrument: {
    key: string;
    name: string;
    logo: string;
    method: string;
    group: string;
    srKey: string | null;
  };
  status?: DowntimeMetaDataType;
  onClick?: (instrument: InstrumentMetaData) => void;
};

const Instrument = ({ instrument, status, onClick }: InstrumentType): JSX.Element => {
  const severity = status?.severity;
  const downtimeType = severity ? STATUS?.[severity] : STATUS.operational;

  const handleOnClick = () => {
    const { key, name, method, group } = instrument;
    trackEcosystemDowntimeEvents(instrumentClick({ key, name, method, group }));
    onClick?.(instrument);
  };

  const { logo, name } = instrument;
  const { text, icon } = downtimeType;

  return (
    <InstrumentItem aria-label="instrument" status={downtimeType} onClick={handleOnClick}>
      <img className="instrument-logo" src={logo} alt={name} />
      <Text
        size="medium"
        truncateAfterLines={1}
        variant="body"
        weight="regular"
        color="surface.text.gray.normal"
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
