import { Badge, OffersIcon } from '@razorpay/blade/components';
import { Location } from 'history';
import React, { useEffect } from 'react';
import { NavLink } from 'react-router-dom';

import Breadcrumb from 'common/components/Breadcrumb';
import { useSplitzService } from 'common/splitz';
import GrowthAssetEB from 'common/ui/GrowthAssetEB';
import * as LocalStorageService from 'common/utils/localStorage';
import { isExperimentActive } from 'common/utils/rzp-utils';
import { StyledHeader } from 'merchant/views/AccountAndSettings/Pricing/Pricing.styles';
import {
  ROUTE_MAP,
  accountAndSettingsLink,
} from 'merchant/views/AccountAndSettings/constants/constants';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

const StreaksReward = ({ location, mode }: { location: Location; mode: string }): JSX.Element => {
  const { abExperiments: { STREAKS_REWARDS_GROWTH } = {} } = useSplitzService();

  useEffect(() => {
    // added localStorage logic to avoid game load in e2e flow
    if (
      LocalStorageService.getItem('CUSTOMER_GLU_E2E') !== 'off' &&
      isExperimentActive(STREAKS_REWARDS_GROWTH) &&
      mode === 'live'
    ) {
      import('merchant/views/Growth/StreaksReferralIncentiveProgram/CustomerGluSdk').then(
        (loadedModule) => {
          loadedModule?.loadCustomerGluSdk();
        },
      );
    }
  }, []);

  return (
    <GrowthAssetEB>
      <div className="tabbed-container">
        <Breadcrumb
          items={[
            accountAndSettingsLink,
            {
              label: ROUTE_MAP[location.pathname],
              link: location.pathname,
            },
          ]}
        />
        <StyledHeader className="scrollable-tab-header">
          <NavLink className="flex-link" to={ROUTES_INFO.STREAK_REWARD} data-testid="flex-link">
            Streaks
            <Badge contrast="low" color="positive" size="medium" icon={OffersIcon}>
              NEW
            </Badge>
          </NavLink>
        </StyledHeader>
      </div>
    </GrowthAssetEB>
  );
};

export default StreaksReward;
