import React from 'react';
import SlabRateSettings from './SlabRateSettings';
import ZoneSetting from './ZoneSettings';
import { PLATFORMS } from 'merchant/views/MagicCheckout/constants';

function BasicFlow({ isRcod, platform }) {
  return (
    <>
      <SlabRateSettings />
      {!isRcod && platform !== PLATFORMS.MAGENTO ? <ZoneSetting /> : null}
    </>
  );
}

export default BasicFlow;
