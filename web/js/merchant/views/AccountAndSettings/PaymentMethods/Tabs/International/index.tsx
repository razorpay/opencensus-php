import React from 'react';

import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';

import OldUi from './International';
import NewUi from './new-ui';

const InternationalSettingsPage = () => {
  const {
    abExperiments: { intl_settings_page_revamp },
  } = useSplitzService();

  const isExpEnabled = isExperimentEnabled(intl_settings_page_revamp);

  return isExpEnabled ? <NewUi /> : <OldUi />;
};

export default InternationalSettingsPage;
