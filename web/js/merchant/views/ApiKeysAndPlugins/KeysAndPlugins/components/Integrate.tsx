import React from 'react';
import { connect } from 'react-redux';

import { trackCTAClick } from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/events';
import {
  MerchantProduct,
  Platform,
  Plugins,
} from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/types';
import {
  INTEGRATION_TITLE,
  INTEGRATION_GUIDE,
} from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/constants';
import IntegrationLinks from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/components/IntegrationLinks';
import { useSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';

interface IntegrateProps {
  product: MerchantProduct;
  selectedPlugin: string;
  selectedPlatform: Platform;
  supportedPlugins: any;
}

const Integrate = ({
  product,
  // from parent
  selectedPlugin,
  selectedPlatform,
  // state from redux
  supportedPlugins,
}: IntegrateProps) => {
  const splitz = useSplitzService();

  const isFtux1Point5Enabled =
    isExperimentEnabled(splitz?.abExperiments?.business_website_v2_automation) &&
    isExperimentEnabled(splitz?.abExperiments?.show_ftux_V_1Point5);
  const plugin = supportedPlugins.items[selectedPlugin];

  const isWebsitePlatform = selectedPlatform === Platform.WEBSITE;
  const shouldShowPlugin = isWebsitePlatform && plugin;
  const integrationGuide =
    shouldShowPlugin && plugin?.integration_guide
      ? plugin.integration_guide
      : INTEGRATION_GUIDE[selectedPlatform];

  const trackProps = {
    paymentChannel: INTEGRATION_TITLE[selectedPlatform],
    product,
  };

  const onIntegrationGuideClick = () => {
    trackCTAClick('Integration Guide', { plugin: selectedPlugin, ...trackProps });
  };

  const onSetupClick = () => {
    trackCTAClick('Go To Setup', { plugin: selectedPlugin, ...trackProps });
  };

  return (
    <div className="keys-plugins-integrate">
      <div className="keys-plugins-step__heading" data-testid="integration-title">
        Integrate with {shouldShowPlugin ? selectedPlugin : INTEGRATION_TITLE[selectedPlatform]}
      </div>
      <div className="keys-plugins-step__content">
        {isFtux1Point5Enabled && isWebsitePlatform && !plugin ? (
          <IntegrationLinks />
        ) : (
          <>
            {shouldShowPlugin && selectedPlugin === Plugins.SHOPIFY && (
              <div>
                Using Razorpay’s plugin for Shopify, you can integrate payments quickly without
                having to get an API key.
              </div>
            )}
            <a
              className="btn btn-link"
              data-testid="integration-guide"
              href={integrationGuide}
              onClick={onIntegrationGuideClick}
              target="_blank"
              rel="noreferrer noopener"
            >
              View integration guide <i className="i i-external-link" />
            </a>
            {shouldShowPlugin && plugin?.integration_url && (
              <a
                className="btn btn-outline btn-block"
                data-testid="integration-url"
                href={plugin?.integration_url || ''}
                onClick={onSetupClick}
                target="_blank"
                rel="noopener noreferrer"
              >
                {selectedPlugin === Plugins.SHOPIFY ? (
                  'Connect with Shopify'
                ) : (
                  <>
                    Go to {plugin?.name} setup <i className="i i-external-link" />
                  </>
                )}
              </a>
            )}
          </>
        )}
      </div>
    </div>
  );
};

export default connect((state) => ({ supportedPlugins: state.plugins.supported }), null)(Integrate);
