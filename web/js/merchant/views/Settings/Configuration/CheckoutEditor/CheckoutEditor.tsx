import React, { useEffect, useMemo } from 'react';
import { Box } from '@razorpay/blade/components';
import {
  AccountConfig,
  AccountLocale,
  MerchantCheckoutConfig,
  MerchantCheckoutStyledConfig,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types';
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
  fetchMerchantCheckoutStylingConfig,
  uploadLogo,
  removeLogo,
  createMerchantCheckoutStylingConfig,
  updateConfig,
} from 'merchant/reducers/config';
import { BrandName } from 'merchant/views/Account/Profile/components/BrandName';
import {
  isFlashCheckoutAllowed,
  isSkipMandatorySummaryPageAllowed,
} from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import { CheckoutDemo } from 'merchant/views/Settings/Configuration/CheckoutDemo';
import CustomMessageSettings from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/CustomMessageSettings/CustomMessageSettings';
import EmailSettings from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/EmailSettings';
import FlashCheckout from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/FlashCheckout';
import LanguageSettings from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/LanguageSettings/LanguageSettings';
import MandatorySummaryPage from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/MandatorySummaryPage';
import BrandColor from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/BrandColor';
import BrandLogo from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/BrandLogo';
import ButtonStyle from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/ButtonStyle/ButttonStyle';
import FontStyle from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/FontStyle/FontStyle';
import SidebarGraphic from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/SidebarGraphic/SidebarGraphic';
import ConfigControls from 'merchant/views/Settings/Configuration/CheckoutEditor/ConfigControls';
import ConfigFooter from 'merchant/views/Settings/Configuration/CheckoutEditor/ConfigFooter';
import {
  CUSTOM_MESSAGE_FEATURE_FLAG,
  CheckoutEditorProvider,
  CheckoutEditorProviderProps,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import { mapCheckoutEmailConfig } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/helpers';
import { showNotification } from 'merchant_common/reducers/notifications';

type CheckoutConfigProps = {
  accountConfig?: AccountConfig;
  accountLocale?: AccountLocale | null;
  merchantCheckoutConfig?: MerchantCheckoutConfig;
  user: User;
  fetchLocale: typeof fetchLocale;
  fetchMerchantCheckoutConfig: typeof fetchMerchantCheckoutConfig;
  fetchMerchantCheckoutStylingConfig: typeof fetchMerchantCheckoutStylingConfig;
  extraConfig: ExtraConfig;
  merchantCheckoutStyledConfig?: MerchantCheckoutStyledConfig;
  showFeatures: boolean;
  showStyling: boolean;
} & Omit<CheckoutEditorProviderProps, 'children'>;

const CheckoutFeatures = ({
  user,
  accountConfig,
  accountLocale,
  merchantCheckoutConfig,
  fetchLocale,
  saveLocale,
  uploadLogo,
  removeLogo,
  updateConfig,
  updateFeatures,
  showNotification,
  fetchMerchantCheckoutConfig,
  fetchMerchantCheckoutStylingConfig,
  createMerchantCheckoutConfig,
  merchantCheckoutStyledConfig,
  createMerchantCheckoutStylingConfig,
  extraConfig,
  showFeatures,
  showStyling,
}: CheckoutConfigProps) => {
  const isCustomMessageFeatureEnabled = useMemo(
    () => !user?.isFeatureEnabled(CUSTOM_MESSAGE_FEATURE_FLAG),
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

  useEffect(() => {
    if (!merchantCheckoutStyledConfig) {
      fetchMerchantCheckoutStylingConfig();
    }
  }, [fetchMerchantCheckoutStylingConfig, merchantCheckoutStyledConfig]);

  const handleUpdateFeatures = (payload: unknown) => updateFeatures(payload, user.current);

  const Features = ({
    isCustomMessageFeatureEnabled,
    extraConfig,
  }: {
    extraConfig: ExtraConfig;
    isCustomMessageFeatureEnabled: boolean;
  }) => {
    return (
      <>
        <LanguageSettings />
        <EmailSettings />
        <ShowWhen additionalCondition={(user) => isFlashCheckoutAllowed(user, extraConfig)}>
          <FlashCheckout />
        </ShowWhen>
        {isCustomMessageFeatureEnabled && <CustomMessageSettings />}
        <ShowWhen additionalCondition={() => isSkipMandatorySummaryPageAllowed(extraConfig)}>
          <MandatorySummaryPage />
        </ShowWhen>
      </>
    );
  };

  const Styles = ({ user }: { user: User }) => {
    return (
      <>
        {user.isAccountAndSettingsRevampEnabled && <BrandName />}
        <BrandColor />
        <ButtonStyle />
        <FontStyle />
        <SidebarGraphic />
        <BrandLogo user={user} />
      </>
    );
  };

  return (
    <CheckoutEditorProvider
      accountConfig={accountConfig}
      accountLocale={accountLocale}
      merchantCheckoutConfig={merchantCheckoutConfig}
      saveLocale={saveLocale}
      uploadLogo={uploadLogo}
      removeLogo={removeLogo}
      updateConfig={updateConfig}
      createMerchantCheckoutConfig={createMerchantCheckoutConfig}
      showNotification={showNotification}
      createMerchantCheckoutStylingConfig={createMerchantCheckoutStylingConfig}
      updateFeatures={handleUpdateFeatures}
      merchantCheckoutStyledConfig={merchantCheckoutStyledConfig}
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
          {showFeatures && (
            <Features
              isCustomMessageFeatureEnabled={isCustomMessageFeatureEnabled}
              extraConfig={extraConfig}
            />
          )}
          {showStyling && <Styles user={user} />}
          <ConfigControls />
          <ConfigFooter />
        </Box>

        <CheckoutDemo />
      </Box>
    </CheckoutEditorProvider>
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
      fetchMerchantCheckoutStylingConfig,
      createMerchantCheckoutStylingConfig,
      updateConfig,
    },
    dispatch,
  );
};

export default connect((state) => {
  const updated_email_config = mapCheckoutEmailConfig(state.config?.email_config);
  return {
    user: state.session.user,
    org: state.session.org,
    accountConfig: {
      ...state.config?.config,
      emailConfig: updated_email_config,
      features: state.config?.features,
    },
    merchantCheckoutStyledConfig: state.config?.checkoutStylingConfig?.data,
    accountLocale: state.config?.locale,
    merchantCheckoutConfig: state.config?.checkoutConfig?.data?.checkout_configuration,
  };
}, mapActionsToProps)(CheckoutFeatures);
