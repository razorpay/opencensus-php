import React, { useEffect, useMemo } from 'react';
import { Box } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { AnyAction, Dispatch, bindActionCreators } from 'redux';

import { User } from 'common/typings';
import {
  fetchLocale,
  uploadLogo,
  removeLogo,
  saveLocale,
  fetchMerchantCheckoutConfig,
  createMerchantCheckoutConfig,
  updateFeatures,
  updateConfig,
} from 'merchant/reducers/config';
import { BrandName } from 'merchant/views/Account/Profile/components/BrandName';
import { CheckoutDemo } from 'merchant/views/Settings/Configuration/CheckoutConfig/CheckoutDemo';
import { showNotification } from 'merchant_common/reducers/notifications';

import BrandColor from './BrandColor';
import BrandLogo from './BrandLogo';
import ConfigControls from './ConfigControls';
import ConfigFooter from './ConfigFooter';
import CustomMessageSettings from './CustomMessageSettings';
import EmailSettings from './EmailSettings';
import LocaleSettings from './LocaleSettings';
import {
  CUSTOM_MESSAGE_FEATURE_FLAG,
  CheckoutConfigProvider,
  CheckoutConfigProviderProps,
} from './context';
import { AccountConfig, AccountLocale, MerchantCheckoutConfig } from './context/types';

type CheckoutConfigProps = {
  accountConfig?: AccountConfig;
  accountLocale?: AccountLocale | null;
  merchantCheckoutConfig?: MerchantCheckoutConfig;
  user: User;
  fetchLocale: typeof fetchLocale;
  fetchMerchantCheckoutConfig: typeof fetchMerchantCheckoutConfig;
} & Omit<CheckoutConfigProviderProps, 'children'>;

const CheckoutConfig = ({
  user,
  accountConfig,
  accountLocale,
  merchantCheckoutConfig,
  fetchLocale,
  uploadLogo,
  removeLogo,
  saveLocale,
  updateConfig,
  updateFeatures,
  showNotification,
  fetchMerchantCheckoutConfig,
  createMerchantCheckoutConfig,
}: CheckoutConfigProps) => {
  const isCustomMessageFeatureEnabled = useMemo(
    () => user?.isFeatureEnabled(CUSTOM_MESSAGE_FEATURE_FLAG),
    [user],
  );

  useEffect(() => {
    if (!accountLocale) {
      fetchLocale();
    }
  }, [fetchLocale, accountLocale]);

  useEffect(() => {
    if (!merchantCheckoutConfig && isCustomMessageFeatureEnabled) {
      fetchMerchantCheckoutConfig();
    }
  }, [fetchMerchantCheckoutConfig, merchantCheckoutConfig, isCustomMessageFeatureEnabled]);

  const handleUpdateFeatures = (payload: unknown) => updateFeatures(payload, user.current);

  return (
    <CheckoutConfigProvider
      accountConfig={accountConfig}
      accountLocale={accountLocale}
      merchantCheckoutConfig={merchantCheckoutConfig}
      uploadLogo={uploadLogo}
      removeLogo={removeLogo}
      saveLocale={saveLocale}
      createMerchantCheckoutConfig={createMerchantCheckoutConfig}
      showNotification={showNotification}
      updateFeatures={handleUpdateFeatures}
      updateConfig={updateConfig}
    >
      <Box
        display="flex"
        flexDirection={{
          base: 'column',
          l: 'row',
        }}
        alignItems="flex-start"
        justifyContent="space-between"
        gap="spacing.11"
      >
        <Box
          flex="2"
          display="flex"
          flexDirection="column"
          gap="spacing.8"
          backgroundColor="surface.background.gray.intense"
          padding="spacing.3"
          maxWidth="600px"
        >
          {user.isAccountAndSettingsRevampEnabled && <BrandName />}
          <BrandColor />
          <BrandLogo user={user} />
          <EmailSettings />
          <LocaleSettings />
          {isCustomMessageFeatureEnabled && <CustomMessageSettings />}
          <ConfigControls />
          <ConfigFooter />
        </Box>

        <CheckoutDemo />
      </Box>
    </CheckoutConfigProvider>
  );
};

const mapActionsToProps = (dispatch: Dispatch<AnyAction>) => {
  return bindActionCreators(
    {
      fetchLocale,
      uploadLogo,
      removeLogo,
      saveLocale,
      updateFeatures,
      showNotification,
      fetchMerchantCheckoutConfig,
      createMerchantCheckoutConfig,
      updateConfig,
    },
    dispatch,
  );
};

export default connect(
  (state) => ({
    user: state.session.user,
    accountConfig: {
      ...state.config?.config,
      emailConfig: state.config?.email_config,
      features: state.config?.features,
    },
    accountLocale: state.config?.locale,
    merchantCheckoutConfig: state.config?.checkoutConfig?.data?.checkout_configuration,
  }),
  mapActionsToProps,
)(CheckoutConfig);
