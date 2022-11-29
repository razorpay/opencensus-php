import React, { useCallback, useEffect, useState } from 'react';
import { PowerSelect } from 'react-power-select';
import { connect } from 'react-redux';
import Spinner from 'common/ui/Spinner';
import * as KeyActions from 'merchant/reducers/keys';
import * as PluginActions from 'merchant/reducers/plugins';
import isEmpty from '@universe/utils/isEmpty';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { isPgMerchant } from 'merchant/components/Activation/ActivationUtils';
import { Platform, Plugins } from './types';
import { INTEGRATION_TITLE, PLATFORM_TITLE } from './constants';
import { getAvailablePlatform, getAvailablePlugin } from './utils';
import { trackCTAClick, trackPluginSelect } from './events';
import {
  AddLink,
  GenerateKey,
  Integrate,
  PluginOption,
  Step,
  Tab,
  TabSwitcher,
} from './components';

const KeysAndPluginsSection = ({
  // state from redux
  keys,
  session,
  merchantPlugins,
  supportedPlugins,
  // action from redux
  fetchKeys,
  saveMerchantPlugin,
  fetchMerchantPlugin,
  fetchSupportedPlugins,
  showNotification,
}) => {
  const { user } = session;
  const defaultPlatform = getAvailablePlatform(user);
  const [selectedPlatform, setSelectedPlatform] = useState<Platform>(defaultPlatform);
  const platformURL = user[selectedPlatform];
  const isWebsitePlatform = selectedPlatform === Platform.WEBSITE;
  const product = isPgMerchant(user) ? 'PG' : 'PH';

  //* Priority for showing default plugin - Merchant Selected Plugin > WhatCMS Suggested Plugin > Empty Select box
  const availablePlugin = getAvailablePlugin(supportedPlugins.items, [
    merchantPlugins.items[platformURL]?.merchant_selected_plugin,
    merchantPlugins.items[platformURL]?.suggested_plugin,
  ]);

  const [plugin, setPlugin] = useState<string | null>(null);
  const selectedPlugin = plugin ?? availablePlugin;

  const getSteps = useCallback(() => {
    const pluginSteps: JSX.Element[] = [];
    //* do not show Generate Key step for Shopify plugin
    if (selectedPlugin !== Plugins.SHOPIFY || !isWebsitePlatform) {
      pluginSteps.push(<GenerateKey product={product} selectedPlatform={selectedPlatform} />);
    }

    pluginSteps.push(
      <Integrate
        product={product}
        selectedPlugin={selectedPlugin}
        selectedPlatform={selectedPlatform}
      />,
    );

    return pluginSteps;
  }, [selectedPlugin, isWebsitePlatform, selectedPlatform]);

  useEffect(() => {
    fetchKeys({ mode: session.mode }, session.user?.has_key_access);
    fetchMerchantPlugin({ merchantId: session.user.current });
    if (isEmpty(supportedPlugins.items)) {
      fetchSupportedPlugins();
    }
  }, []);

  const onTabChange = (platform) => {
    setSelectedPlatform(platform as Platform);
    trackCTAClick('Payment Channel', {
      paymentChannel: INTEGRATION_TITLE[platform as Platform],
      product,
    });
  };

  const onPluginChange = (updatedPlugin: string) => {
    const previousPlugin = selectedPlugin;
    if (previousPlugin === updatedPlugin) return;
    setPlugin(updatedPlugin);
    trackPluginSelect({
      plugin: updatedPlugin,
      paymentChannel: INTEGRATION_TITLE[selectedPlatform],
      product,
    });
    saveMerchantPlugin({
      merchantId: session.user?.current,
      data: {
        website: session.user?.[selectedPlatform],
        plugin_name: updatedPlugin,
      },
    })
      .then(() => {
        showNotification({
          type: 'success',
          message: 'Plugin saved successfully',
        });
        fetchMerchantPlugin({ merchantId: session.user.current });
      })
      .catch(({ errors }) => {
        showNotification({
          type: 'error',
          message: errors?.[0],
        });
        setPlugin(previousPlugin);
      });
  };

  if (!keys.isLoaded || supportedPlugins.loading || merchantPlugins.loading) {
    return (
      <div className="page-spinner-container">
        <Spinner />
      </div>
    );
  }

  return (
    <div className="keys-plugins-section-wrapper">
      <div className="keys-plugins-section">
        <div className="keys-plugins-section__header">
          <div className="keys-plugins-section__heading">
            Let’s integrate payments with your website/app
          </div>

          <TabSwitcher defaultTab={defaultPlatform} onChange={onTabChange}>
            {Object.values(Platform).map((platform) => (
              <Tab key={platform} id={platform}>
                <i
                  className={`i ${
                    platform === Platform.WEBSITE ? 'i-payment-page' : 'i-smartphone'
                  }`}
                />
                <span className="hidden-sm">{PLATFORM_TITLE[platform]}</span>
                <span className="display-sm">{INTEGRATION_TITLE[platform]}</span>
              </Tab>
            ))}
          </TabSwitcher>
          {isWebsitePlatform && platformURL && (
            <div>
              <label className="control-label">Website platform</label>

              <PowerSelect
                onChange={(args) => onPluginChange(args.option.name)}
                optionLabelPath="name"
                selected={selectedPlugin}
                placeholder="-- Select Platform --"
                showClear={false}
                searchEnabled={false}
                options={Object.values(supportedPlugins.items)}
                optionComponent={({ option }) => <PluginOption plugin={option} />}
                selectedOptionComponent={({ option }) => (
                  <PluginOption plugin={supportedPlugins.items[option]} selected={true} />
                )}
              />
            </div>
          )}
        </div>
        {!!platformURL ? (
          getSteps().map((component, index, steps) => (
            <Step step={index + 1} borderBottom={index !== steps.length - 1} key={index}>
              {component}
            </Step>
          ))
        ) : (
          <AddLink platform={selectedPlatform} product={product} />
        )}
      </div>
    </div>
  );
};

export default connect(
  (state) => ({
    session: state.session,
    supportedPlugins: state.plugins.supported,
    merchantPlugins: state.plugins.details,
    keys: state.keys,
  }),
  { ...KeyActions, ...PluginActions, ...NotificationsActions },
)(KeysAndPluginsSection);
