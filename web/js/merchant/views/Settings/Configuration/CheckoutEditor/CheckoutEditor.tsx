import React, { useEffect, useMemo } from 'react';
import { Box } from '@razorpay/blade/components';
import {
  AccountConfig,
  AccountLocale,
  Blocks,
  MerchantCheckoutConfig,
  MerchantCheckoutPaymentConfigs,
  MerchantCheckoutStyledConfig,
  TrustedBadgeType,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context/types';
import { connect } from 'react-redux';
import { AnyAction, Dispatch, bindActionCreators } from 'redux';

import { useSplitzService } from 'common/splitz';
import { User } from 'common/typings';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import { isExperimentActive } from 'common/utils/rzp-utils';
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
  uploadWordmark,
  removeLogo,
  createSuggestion,
  createFeedback,
  fetchBlocks,
  createMerchantCheckoutStylingConfig,
  createMerchantCheckoutBrandConfig,
  updateConfig,
  updateEmailConfig,
  fetchMerchantCheckoutPaymentConfigurations,
} from 'merchant/reducers/config';
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
import CheckoutStyles from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyles';
import ConfigControls from 'merchant/views/Settings/Configuration/CheckoutEditor/ConfigControls';
import ConfigFooter from 'merchant/views/Settings/Configuration/CheckoutEditor/ConfigFooter';
import { PaymentConfiguration } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/PaymentConfiguration';
import Suggestion from 'merchant/views/Settings/Configuration/CheckoutEditor/Suggestion';
import {
  PROD_BLOCKS_IDs,
  STAGE_BLOCKS_IDs,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/constant';
import {
  CUSTOM_MESSAGE_FEATURE_FLAG,
  CheckoutEditorProvider,
  CheckoutEditorProviderProps,
} from 'merchant/views/Settings/Configuration/CheckoutEditor/context';
import { mapCheckoutEmailConfig } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/helpers';
import blockAnalytics from 'merchant/views/Settings/Configuration/CheckoutEditor/track/blockAnalytics';
import ComingSoonLineItems from 'merchant/views/Settings/Configuration/components/Configuration/ComingSoonLineItem';
import { showNotification } from 'merchant_common/reducers/notifications';

type CheckoutConfigProps = {
  accountConfig?: AccountConfig;
  accountLocale?: AccountLocale | null;
  merchantCheckoutConfig?: MerchantCheckoutConfig;
  suggestion?: any;
  user: User;
  blocks?: Blocks;
  fetchBlocks: typeof fetchBlocks;
  fetchLocale: typeof fetchLocale;
  fetchMerchantCheckoutConfig: typeof fetchMerchantCheckoutConfig;
  fetchMerchantCheckoutStylingConfig: typeof fetchMerchantCheckoutStylingConfig;
  createSuggestion: typeof createSuggestion;
  createFeedback: typeof createFeedback;
  extraConfig: ExtraConfig;
  merchantCheckoutStyledConfig?: MerchantCheckoutStyledConfig;
  merchantCheckoutPaymentConfigs: MerchantCheckoutPaymentConfigs;
  showFeatures: boolean;
  showStyling: boolean;
  showPaymentConfiguration: boolean;
  trustedBadge: TrustedBadgeType;
  updateEmailConfig: typeof updateEmailConfig;
  fetchMerchantCheckoutPaymentConfigurations: typeof fetchMerchantCheckoutPaymentConfigurations;
} & Omit<CheckoutEditorProviderProps, 'children'>;

const CheckoutEditor = ({
  user,
  accountConfig,
  accountLocale,
  merchantCheckoutConfig,
  suggestion,
  fetchLocale,
  saveLocale,
  uploadLogo,
  uploadWordmark,
  removeLogo,
  updateConfig,
  updateFeatures,
  showNotification,
  fetchMerchantCheckoutConfig,
  fetchMerchantCheckoutStylingConfig,

  createMerchantCheckoutConfig,
  createSuggestion,
  createFeedback,
  blocks,
  fetchBlocks,
  merchantCheckoutStyledConfig,
  createMerchantCheckoutStylingConfig,
  createMerchantCheckoutBrandConfig,
  extraConfig,
  showFeatures,
  showStyling,
  showPaymentConfiguration,
  trustedBadge,
  updateEmailConfig,
  fetchMerchantCheckoutPaymentConfigurations,
  merchantCheckoutPaymentConfigs,
}: CheckoutConfigProps) => {
  const isCustomMessageFeatureEnabled = useMemo(
    () => !user?.isFeatureEnabled(CUSTOM_MESSAGE_FEATURE_FLAG),
    [user],
  );

  const {
    abExperiments: { checkout_editor_payment_config },
  } = useSplitzService();

  const isPaymentConfigEnabled = isExperimentActive(checkout_editor_payment_config);

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
    if (merchantCheckoutStyledConfig && Object.keys(merchantCheckoutStyledConfig).length === 0) {
      fetchMerchantCheckoutStylingConfig();
    }
  }, [fetchMerchantCheckoutStylingConfig, merchantCheckoutStyledConfig]);

  useEffect(() => {
    triggerHotjarRecording('CHECKOUT_EDITOR_SCREEN', ['CHECKOUT_EDITOR_SCREEN']);
  }, []);

  useEffect(() => {
    fetchBlocks();
  }, [fetchBlocks]);

  useEffect(() => {
    if (isPaymentConfigEnabled) {
      fetchMerchantCheckoutPaymentConfigurations();
    }
  }, [fetchMerchantCheckoutPaymentConfigurations, isPaymentConfigEnabled]);

  useEffect(() => {
    if (blocks) {
      blockAnalytics(blocks);
    }
  }, [blocks]);

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
        {blocks?.map((block) => {
          switch (block.id) {
            case PROD_BLOCKS_IDs.emailSettings:
            case STAGE_BLOCKS_IDs.emailSettings:
              return <EmailSettings key={block.id} blockData={block} />;
            case PROD_BLOCKS_IDs.flashCheckout:
            case STAGE_BLOCKS_IDs.flashCheckout:
              return (
                <ShowWhen
                  key={block.id}
                  additionalCondition={(user) => isFlashCheckoutAllowed(user, extraConfig)}
                >
                  <FlashCheckout blockData={block} />
                </ShowWhen>
              );
            case PROD_BLOCKS_IDs.customMessageSettings:
            case STAGE_BLOCKS_IDs.customMessageSettings:
              return isCustomMessageFeatureEnabled ? (
                <CustomMessageSettings key={block.id} blockData={block} />
              ) : null;
            case PROD_BLOCKS_IDs.mandatorySummaryPage:
            case STAGE_BLOCKS_IDs.mandatorySummaryPage:
              return (
                <ShowWhen
                  key={block.id}
                  additionalCondition={() => isSkipMandatorySummaryPageAllowed(extraConfig)}
                >
                  <MandatorySummaryPage blockData={block} />
                </ShowWhen>
              );
            case PROD_BLOCKS_IDs.languageSettings:
            case STAGE_BLOCKS_IDs.languageSettings:
              return <LanguageSettings key={block.id} blockData={block} />;
            default:
              return block?.tags
                .filter((tagData) => tagData?.tag === 'coming soon')
                .map((tag) => (
                  <ComingSoonLineItems
                    key={tag?.id}
                    title={tag?.name}
                    subTitle={tag?.description}
                  />
                ));
          }
        })}
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
      uploadWordmark={uploadWordmark}
      removeLogo={removeLogo}
      updateConfig={updateConfig}
      createSuggestion={createSuggestion}
      createFeedback={createFeedback}
      createMerchantCheckoutConfig={createMerchantCheckoutConfig}
      showNotification={showNotification}
      createMerchantCheckoutStylingConfig={createMerchantCheckoutStylingConfig}
      createMerchantCheckoutBrandConfig={createMerchantCheckoutBrandConfig}
      updateFeatures={handleUpdateFeatures}
      merchantCheckoutStyledConfig={merchantCheckoutStyledConfig}
      merchantCheckoutPaymentConfigs={merchantCheckoutPaymentConfigs}
      updateEmailConfig={updateEmailConfig}
      trustedBadge={trustedBadge}
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
        height={{
          l: '70vh',
        }}
      >
        <Box
          flex="2"
          display="flex"
          flexDirection="column"
          gap="spacing.4"
          backgroundColor="surface.background.gray.intense"
          maxWidth="600px"
          height="100%"
        >
          <Box
            display="flex"
            flexDirection="column"
            gap="spacing.4"
            padding="spacing.3"
            overflowY="auto"
            flex="1"
          >
            {showFeatures && (
              <Features
                isCustomMessageFeatureEnabled={isCustomMessageFeatureEnabled}
                extraConfig={extraConfig}
              />
            )}
            {showStyling && <CheckoutStyles trustedBadge={trustedBadge} />}
            {showPaymentConfiguration && <PaymentConfiguration />}
            {!suggestion && <Suggestion />}
            <ConfigFooter />
          </Box>
          <ConfigControls />
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
      uploadWordmark,
      removeLogo,
      saveLocale,
      updateFeatures,
      showNotification,
      fetchMerchantCheckoutConfig,
      createMerchantCheckoutConfig,
      createSuggestion,
      createFeedback,
      fetchBlocks,
      fetchMerchantCheckoutStylingConfig,
      createMerchantCheckoutStylingConfig,
      fetchMerchantCheckoutPaymentConfigurations,
      createMerchantCheckoutBrandConfig,
      updateConfig,
      updateEmailConfig,
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
    suggestion: state.config?.suggestion,
    blocks: state.config?.blocks?.data?.dashboard_blocks,
    merchantCheckoutStyledConfig: state.config?.checkoutStylingConfig?.data,
    accountLocale: state.config?.locale,
    merchantCheckoutConfig: state.config?.checkoutConfig?.data?.checkout_configuration,
    merchantCheckoutPaymentConfigs: state.config?.checkoutPaymentConfigs,
    trustedBadge: state.trustedBadge,
  };
}, mapActionsToProps)(CheckoutEditor);
