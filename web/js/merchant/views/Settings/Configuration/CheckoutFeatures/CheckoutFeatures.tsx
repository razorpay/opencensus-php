import React, { useEffect, useMemo } from 'react';
import { Box } from '@razorpay/blade/components';
import { AccountConfig } from 'merchant/views/Settings/Configuration/CheckoutConfig/context/types';
import { connect } from 'react-redux';
import { AnyAction, Dispatch, bindActionCreators } from 'redux';

import { User } from 'common/typings';
import ShowWhen from 'merchant/components/ShowWhen';
import { ExtraConfig } from 'merchant/components/SidebarV2/utils/Products';
import {
  fetchLocale,
  saveLocale,
  fetchMerchantCheckoutConfig,
  createMerchantCheckoutConfig,
  updateFeatures,
} from 'merchant/reducers/config';
import { BrandName } from 'merchant/views/Account/Profile/components/BrandName';
import { isFlashCheckoutAllowed } from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import { CheckoutDemo } from 'merchant/views/Settings/Configuration/CheckoutDemo';
import { showNotification } from 'merchant_common/reducers/notifications';

import ConfigControls from './ConfigControls';
import ConfigFooter from './ConfigFooter';
import CustomMessageSettings from './CustomMessageSettings';
import EmailSettings from './EmailSettings';
import FlashCheckout from './FlashCheckout';
import LocaleSettings from './LocaleSettings';
import {
  CUSTOM_MESSAGE_FEATURE_FLAG,
  CheckoutConfigProvider,
  CheckoutConfigProviderProps,
} from './context';
import { AccountLocale, MerchantCheckoutConfig } from './context/types';

type CheckoutConfigProps = {
  accountConfig?: AccountConfig;
  accountLocale?: AccountLocale | null;
  merchantCheckoutConfig?: MerchantCheckoutConfig;
  user: User;
  fetchLocale: typeof fetchLocale;
  fetchMerchantCheckoutConfig: typeof fetchMerchantCheckoutConfig;
  extraConfig: ExtraConfig;
} & Omit<CheckoutConfigProviderProps, 'children'>;

const CheckoutFeatures = ({
  user,
  accountConfig,
  accountLocale,
  merchantCheckoutConfig,
  fetchLocale,
  saveLocale,
  updateFeatures,
  showNotification,
  fetchMerchantCheckoutConfig,
  createMerchantCheckoutConfig,
  extraConfig,
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
      saveLocale={saveLocale}
      createMerchantCheckoutConfig={createMerchantCheckoutConfig}
      showNotification={showNotification}
      updateFeatures={handleUpdateFeatures}
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
          <LocaleSettings />
          <EmailSettings />
          <ShowWhen additionalCondition={(user) => isFlashCheckoutAllowed(user, extraConfig)}>
            <FlashCheckout />
          </ShowWhen>
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
      saveLocale,
      updateFeatures,
      showNotification,
      fetchMerchantCheckoutConfig,
      createMerchantCheckoutConfig,
    },
    dispatch,
  );
};

export default connect((state) => {
  return {
    user: state.session.user,
    org: state.session.org,
    accountConfig: {
      ...state.config?.config,
      emailConfig: state.config?.email_config,
      features: state.config?.features,
    },
    accountLocale: state.config?.locale,
    merchantCheckoutConfig: state.config?.checkoutConfig?.data?.checkout_configuration,
  };
}, mapActionsToProps)(CheckoutFeatures);
