import React, { useMemo } from 'react';
import { connect } from 'react-redux';

import {
  ROUTES,
  DEFAULT_ROUTES,
  PLATFORMS,
  PATH_PREFIX,
} from 'merchant/views/MagicCheckout/Settings/constants';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import VerticalNavContainer from 'merchant/views/MagicCheckout/common/components/VerticalNavContainer';

import { PlatformSpecificRoutes, GenericRecord } from 'merchant/views/MagicCheckout/types';
import { useMagicExperiment } from 'merchant/views/MagicCheckout/utils/useMagicExperiment';

interface NestedVerticalTabProps {
  settings: GenericRecord;
  magicCheckout: GenericRecord;
}

const SetupAndSettings: React.FC<NestedVerticalTabProps> = ({ settings, magicCheckout }) => {
  const isMagicXPublicappCodEnabled = useMagicExperiment('magicx_publicapp_cod');
  const { platform, one_click_checkout = true } = settings;
  const { cod_order_control: isCODOrderControlEnabled, rcod: isRCOD } = magicCheckout;

  const NAV_ITEMS: PlatformSpecificRoutes = useMemo(() => {
    return platform === PLATFORMS?.NATIVE || one_click_checkout
      ? { ...ROUTES }
      : { ...DEFAULT_ROUTES };
  }, [platform, one_click_checkout]);

  const customRouteCheck = (item, user) => {
    /**
     * Checkout360 Experience
     * Splitz experiment in use: `magicx_publicapp_cod`
     * When the experiment in ON:
     * -> if user has not completed C360 onboarding, we change the route label and
     *    hide all other routes
     * -> if user has completed C360 onboarding, we do nothing
     */
    if (
      isMagicXPublicappCodEnabled &&
      platform === 'shopify' &&
      isRCOD &&
      (user.isC360OnboardingCompleted || user.isC360OnboardingToBeResumed)
    ) {
      if (!user.isC360OnboardingCompleted) {
        // We need to check both label values for stability across component re-renders
        if (item.label === 'Control Center' || item.label === 'Checkout360') {
          item.label = 'Checkout360';
          return true;
        } else {
          return false;
        }
      }
    }

    return !(
      item.label === 'COD Review Workflow' &&
      (!isCODOrderControlEnabled || !user?.isMagicCODOrderAutomationEnabled)
    );
  };

  //Common Component to render L2 Navigation
  return (
    <SuspenseWithLoader type="center">
      <VerticalNavContainer
        navItems={NAV_ITEMS}
        basePath={PATH_PREFIX}
        customRouteCheck={customRouteCheck}
      />
    </SuspenseWithLoader>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
  magicCheckout: state.magicCheckout,
});

export default connect(mapStateToProps, null)(SetupAndSettings);
