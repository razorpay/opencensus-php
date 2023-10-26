import React from 'react';
import SlabRateSettings from './SlabRateSettings';
import ZoneSetting from './ZoneSettings';
function BasicFlow({ isRcod }) {
  return (
    <>
      <SlabRateSettings />
      {!isRcod ? <ZoneSetting /> : null}
    </>
  );
}

export default BasicFlow;
