import React from 'react';

import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';
import { User } from 'common/typings';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';

const SettlementSkipBanner: React.FC<{
  user: User;
}> = ({ user }) => {
  const splitz = useSplitzService();

  // comparing with Unix timestamp for 21th November 2024 00:00:01
  const isItTillHoliday = Date.now() < 1732127401000;

  const shouldShowBanner =
    isExperimentEnabled(splitz?.abExperiments?.settlement_skip_banner) &&
    user?.isCountryIndia &&
    isItTillHoliday;

  return shouldShowBanner ? (
    <AnnouncementBanner title="No Settlement on 20th November" theme="warning">
      Due to the Maharashtra elections, settlements will be paused on 20th November. Regular
      settlement processing will resume on 21st November. Thank you for your understanding.
    </AnnouncementBanner>
  ) : null;
};

export default SettlementSkipBanner;
