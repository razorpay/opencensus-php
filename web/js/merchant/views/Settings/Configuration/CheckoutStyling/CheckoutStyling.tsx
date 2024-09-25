import React, { useEffect, useMemo } from 'react';
import { Box } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { AnyAction, Dispatch, bindActionCreators } from 'redux';

import { User } from 'common/typings';
import {
  fetchLocale,
  uploadLogo,
  removeLogo,
  fetchMerchantCheckoutConfig,
  updateConfig,
} from 'merchant/reducers/config';
import { BrandName } from 'merchant/views/Account/Profile/components/BrandName';
import { CheckoutDemo } from 'merchant/views/Settings/Configuration/CheckoutDemo';
import { showNotification } from 'merchant_common/reducers/notifications';
import BrandLogo from './BrandLogo';
import BrandColor from './BrandColor';
import ConfigControls from './ConfigControls';
import ConfigFooter from './ConfigFooter';
import {
  CUSTOM_MESSAGE_FEATURE_FLAG,
  CheckoutConfigProvider,
  CheckoutConfigProviderProps,
} from './context';
import {
  AccountConfig,
  AccountLocale,
  MerchantCheckoutConfig,
} from 'merchant/views/Settings/Configuration/CheckoutStyling/context/types';
import { mapCheckoutEmailConfig } from 'merchant/views/Settings/Configuration/CheckoutStyling/context/helpers';

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
  updateConfig,
  showNotification,
  fetchMerchantCheckoutConfig,
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

  return (
    <CheckoutConfigProvider
      accountConfig={accountConfig}
      uploadLogo={uploadLogo}
      removeLogo={removeLogo}
      showNotification={showNotification}
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
          gap="spacing.4"
          backgroundColor="surface.background.gray.intense"
          padding="spacing.3"
          maxWidth="600px"
        >
          {user.isAccountAndSettingsRevampEnabled && <BrandName />}
          <BrandColor />
          <BrandLogo user={user} />
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
      showNotification,
      updateConfig,
    },
    dispatch,
  );
};

export default connect((state) => {
  const updated_email_config = mapCheckoutEmailConfig(state.config?.email_config);
  return {
    user: state.session.user,
    accountConfig: {
      ...state.config?.config,
      emailConfig: updated_email_config,
      features: state.config?.features,
    },
    accountLocale: state.config?.locale,
    merchantCheckoutConfig: state.config?.checkoutConfig?.data?.checkout_configuration,
  };
}, mapActionsToProps)(CheckoutConfig);
