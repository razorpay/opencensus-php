import React from 'react';
import { connect } from 'react-redux';

import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';

import OldUi from './International';
import NewUi from './new-ui';

const InternationalSettingsPage = ({ isCbMkycMerchant }: { isCbMkycMerchant: boolean }) => {
  const {
    abExperiments: { intl_settings_page_revamp },
  } = useSplitzService();

  const isExpEnabled = isExperimentEnabled(intl_settings_page_revamp) || isCbMkycMerchant;

  return isExpEnabled ? <NewUi /> : <OldUi />;
};

export default connect((state) => ({ isCbMkycMerchant: state.session.user?.isCbMkycMerchant }))(
  InternationalSettingsPage,
);
