import Spinner from 'common/ui/Spinner';
import isEmpty from 'lodash/isEmpty';
import { isPgMerchant } from 'merchant/components/Activation/ActivationUtils';
import * as KeyActions from 'merchant/reducers/keys';
import * as PluginActions from 'merchant/reducers/plugins';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import React, { useEffect, useState } from 'react';
import { PowerSelect } from 'react-power-select';
import { connect } from 'react-redux';
import {
  AddLink,
  GenerateKey,
  Integrate,
  PluginOption,
  Step,
  Tab,
  TabSwitcher,
} from './components';
import { INTEGRATION_TITLE, NO_PLUGIN_OPTION, PLATFORM_TITLE } from './constants';
import { trackCTAClick, trackPluginSelect } from './events';
import { Platform } from './types';
import { getAvailablePlatform, getAvailablePlugin, getProvidedChannels } from './utils';

const KeysAndPluginsSection = ({
  // state from redux
  keys,
  mode,
  user,
  pluginDetails,
  supportedPlugins,
  // action from redux
  fetchKeys,
  saveMerchantPlugin,
  fetchMerchantPlugin,
  fetchSupportedPlugins,
  showNotification,
  showProvidedChannels = false,
  switchMode,
}) => {
  const providedChannels = getProvidedChannels(user);
  const defaultPlatform = getAvailablePlatform(user, { showProvidedChannels });
  const [selectedPlatform, setSelectedPlatform] = useState<Platform>(defaultPlatform as Platform);
  const platformURL = user[selectedPlatform];
  const isWebsitePlatform = selectedPlatform === Platform.WEBSITE;
  const tabs = showProvidedChannels ? providedChannels : Object.values(Platform);
  const product = isPgMerchant(user) ? 'PG' : 'PH';

  // user should have key access, user should not fully activated and user has payment enabled
  const userHasKeyAccess = user.has_key_access && !user.isAccepted && user.isActivated;

  //* Priority for showing default plugin - Merchant Selected Plugin > WhatCMS Suggested Plugin > Empty Select box

  const availablePlugin = getAvailablePlugin({
    supportedPlugins: supportedPlugins.items,
    merchantSelectedPlugin: pluginDetails.items[platformURL]?.merchant_selected_plugin,
    suggestedPlugin: pluginDetails.items[platformURL]?.suggested_plugin,
  });

  const [plugin, setPlugin] = useState<string | null>(null);
  const selectedPlugin = plugin ?? availablePlugin;

  const options = Object.values(supportedPlugins.items).concat([NO_PLUGIN_OPTION]);

  const pluginSteps: JSX.Element[] = [
    <GenerateKey
      product={product}
      selectedPlatform={selectedPlatform}
      key="generate"
      switchMode={switchMode}
    />,
    <Integrate
      product={product}
      selectedPlugin={selectedPlugin}
      selectedPlatform={selectedPlatform}
      key="integrate"
    />,
  ];

  useEffect(() => {
    fetchKeys({ mode }, user?.has_key_access);
    fetchMerchantPlugin({ merchantId: user.current });
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
      merchantId: user?.current,
      data: {
        website: user?.[selectedPlatform],
        plugin_name: updatedPlugin === NO_PLUGIN_OPTION.name ? '' : updatedPlugin,
      },
    })
      .then(() => {
        showNotification({
          type: 'success',
          message: 'Plugin saved successfully',
        });
        fetchMerchantPlugin({ merchantId: user?.current });
      })
      .catch(({ errors }) => {
        showNotification({
          type: 'error',
          message: errors?.[0],
        });
        setPlugin(previousPlugin);
      });
  };

  if (!keys.isLoaded || supportedPlugins.loading || pluginDetails.loading) {
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

          {tabs.length > 1 ? (
            <TabSwitcher defaultTab={defaultPlatform} onChange={onTabChange}>
              {tabs.map((platform) => (
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
          ) : null}
          {isWebsitePlatform && platformURL && (
            <div>
              <label className="control-label">Website platform</label>

              <PowerSelect
                onChange={(args) => onPluginChange(args.option?.name)}
                optionLabelPath="name"
                selected={selectedPlugin}
                placeholder="-- Select Platform --"
                showClear={false}
                searchEnabled={false}
                options={options}
                optionComponent={({ option }) => <PluginOption plugin={option} />}
                selectedOptionComponent={({ option }) => (
                  <PluginOption
                    plugin={
                      option === NO_PLUGIN_OPTION.name
                        ? NO_PLUGIN_OPTION
                        : supportedPlugins.items[option]
                    }
                    selected={true}
                  />
                )}
              />
            </div>
          )}
        </div>

        {!!platformURL ? (
          pluginSteps.map((component, index, steps) => (
            <Step step={index + 1} borderBottom={index !== steps.length - 1} key={index}>
              {component}
            </Step>
          ))
        ) : (
          <AddLink
            platform={selectedPlatform}
            product={product}
            userHasKeyAccess={userHasKeyAccess}
          />
        )}
      </div>
    </div>
  );
};

export default connect(
  (state) => ({
    mode: state.session.mode,
    user: state.session.user,
    supportedPlugins: state.plugins.supported,
    pluginDetails: state.plugins.details,
    keys: state.keys,
  }),
  { ...KeyActions, ...PluginActions, ...NotificationsActions },
)(KeysAndPluginsSection);
