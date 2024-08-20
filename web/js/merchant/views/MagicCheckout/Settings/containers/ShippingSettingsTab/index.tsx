import React, { useState, useEffect } from 'react';
import { Box, Heading, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import Spinner from 'common/ui/Spinner';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { fetchSummary } from 'merchant/reducers/magicCheckout/shippingEngine/action';
import { ShippingEngineStore } from 'merchant/reducers/magicCheckout/shippingEngine/types';
import lazy from 'merchant/routes/LazyLoader';
import SettingsToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle';
import {
  PLATFORMS,
  ENABLE_MAGIC_SHIPPING,
} from 'merchant/views/MagicCheckout/MagicSettings/constants';
import {
  SWITCH_TEXTS,
  SHIPPING_SETTINGS_INFO,
  MAGIC_SHIPPING_DESCRIPTION,
  RCOD_SHIPPING_DESCRIPTION,
  RCOD_SHIPPING_NOTE,
  RCOD_SHIPPING_SETTINGS_INFO,
} from 'merchant/views/MagicCheckout/Settings/constants';
import { MODAL_TEXTS } from 'merchant/views/MagicCheckout/ShippingSettings/constants';
import ConfirmationModal, {
  DisplayNotificationTxt,
} from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import PreventDeleteModal from 'merchant/views/MagicCheckout/common/components/PreventDeleteModal';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import { useMagicExperiment } from 'merchant/views/MagicCheckout/utils/useMagicExperiment';

import { ShippingSettingsWrapper, ShippingToggle } from './styles';
import { verifyIfProfilesAreConfigured } from './helpers';

import { StyledPluginUpdateWrapper } from 'merchant/views/MagicCheckout/MagicSettings/styled';
import { StyledRCODShippingNoteWrapper } from 'merchant/views/MagicCheckout/Settings/containers/styledComponents';
import { MAGIC_DASHBOARD_REVAMP_EXPERIMENT } from 'merchant/views/MagicCheckout/constants';

const ShippingSettings = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicShippingSettings" */ 'merchant/views/MagicCheckout/ShippingSettings'
    ),
);

const ShippingSettingsTab = ({
  settings,
  shippingEngine,
  updateSettings,
  openModal,
  closeModal,
  showNotification,
  fetchSummary,
  isRCOD,
}) => {
  const { shipping_engine, platform, shop_id, one_cc_international_shipping } = settings;
  const [shippingSettings, setShippingSettings] = useState(shipping_engine || false);
  const [internationalShipping, setInternationalShipping] = useState(
    one_cc_international_shipping || false,
  );
  const [isIntlShippingLoading, setIntlShippingLoading] = useState(false);
  const { shipping_profiles, isLoading } = shippingEngine as ShippingEngineStore;
  const isMagicDashboardV2Enabled = useMagicExperiment(MAGIC_DASHBOARD_REVAMP_EXPERIMENT);

  useEffect(() => {
    fetchSummary();
  }, []);

  const switchShippingSettingsMode = (toggleState) => {
    const modalAction = !toggleState ? 'enabled' : 'disabled';
    const params: Record<string, unknown> = {
      shipping_engine: !shippingSettings,
      platform,
    };

    if (platform === PLATFORMS.VALUES.SHOPIFY) {
      params.shop_id = shop_id;
    }

    updateSettings(params)
      .then(() => {
        showNotification({
          type: 'success',
          message: () => (
            <DisplayNotificationTxt
              notificationTxt={`Magic shipping ${modalAction} successfully`}
            />
          ),
        });

        setShippingSettings((prevState) => {
          return !prevState;
        });
      })
      .finally(() => {
        closeModal();
      });
  };

  const handleToggleClick = () => {
    if (!shippingSettings) {
      const isEngineConfigured = verifyIfProfilesAreConfigured(shipping_profiles);
      if (!isEngineConfigured) {
        openModal({
          size: 'small',
          className: `magicToggleConfirmationModal`,
          component: (
            <PreventDeleteModal
              header={MODAL_TEXTS.PROFILE_NOT_CONFIGURED.header}
              description={MODAL_TEXTS.PROFILE_NOT_CONFIGURED.description}
            />
          ),
        });
        return;
      }
    }
    const modalState = !shippingSettings ? 'enable' : 'disable';
    const modalAction = () => switchShippingSettingsMode(shippingSettings);
    const { header, desc, subText, secondaryCtaLabel, primaryCtaLabel } =
      SWITCH_TEXTS[modalState].shippingSettings;

    openModal({
      size: 'small',
      className: `magicToggleConfirmationModal`,
      component: (
        <ConfirmationModal
          header={header}
          subText={subText}
          desc={desc}
          affirmativeLabel={primaryCtaLabel}
          abortLabel={secondaryCtaLabel}
          onAffirm={modalAction}
        />
      ),
    });
  };

  const handleInternationalShipping = () => {
    setIntlShippingLoading(true);
    const params: Record<string, unknown> = {
      one_cc_international_shipping: !internationalShipping,
      platform,
      shop_id,
    };
    const modalState = !internationalShipping ? 'enabled' : 'disabled';
    updateSettings(params)
      .then(() => {
        showNotification({
          type: 'success',
          message: () => (
            <DisplayNotificationTxt
              notificationTxt={`International shipping ${modalState} successfully`}
            />
          ),
        });
        setInternationalShipping((prevState) => {
          return !prevState;
        });
      })
      .catch((res) => {
        showNotification({
          type: 'error',
          message: () => <DisplayNotificationTxt notificationTxt={res?.errors?.join('')} />,
        });
      })
      .finally(() => {
        setIntlShippingLoading(false);
      });
  };

  return (
    <>
      <StyledPluginUpdateWrapper>{ENABLE_MAGIC_SHIPPING}</StyledPluginUpdateWrapper>
      <ShippingSettingsWrapper>
        <Heading size="medium">Shipping Settings </Heading>
        {isMagicDashboardV2Enabled &&
          (isIntlShippingLoading ? (
            <Spinner center={false} />
          ) : (
            <ShippingToggle>
              <SettingsToggle
                setting={{
                  label: 'International Shipping ',
                  value: internationalShipping,
                  description: 'Allow customers to select international pin code for delivery',
                }}
                onToggle={handleInternationalShipping}
              />
            </ShippingToggle>
          ))}
        <Text size="medium" marginTop="spacing.4" color="surface.text.gray.muted">
          {!isRCOD ? SHIPPING_SETTINGS_INFO : RCOD_SHIPPING_SETTINGS_INFO}
        </Text>
        {!isRCOD ? (
          <ShippingToggle>
            <SettingsToggle
              setting={{ label: 'Magic Shipping ', value: shippingSettings }}
              onToggle={handleToggleClick}
            />
          </ShippingToggle>
        ) : null}
        <Box marginTop="spacing.2">
          <Text size="small" color="surface.text.gray.muted">
            {!isRCOD ? MAGIC_SHIPPING_DESCRIPTION : RCOD_SHIPPING_DESCRIPTION}
          </Text>
        </Box>
        {isRCOD ? (
          <Box padding="spacing.6" paddingLeft="spacing.0" paddingRight="spacing.0">
            <StyledRCODShippingNoteWrapper>{RCOD_SHIPPING_NOTE}</StyledRCODShippingNoteWrapper>
          </Box>
        ) : null}
        <Box marginY="spacing.6">
          <ErrorBoundary team={Teams?.MAGIC_CHECKOUT} rank={Ranks.P0} resetOnProps>
            <SuspenseWithLoader type="center">
              {isLoading.summary ? (
                <div className="page-spinner-container">
                  <Spinner center />
                </div>
              ) : (
                <ShippingSettings />
              )}
            </SuspenseWithLoader>
          </ErrorBoundary>
        </Box>
      </ShippingSettingsWrapper>
    </>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
  shippingEngine: state.magicShippingEngine,
  merchantId: state.config?.config?.id,
  isRCOD: state.magicCheckout.rcod,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateSettings: updateMagicSettings,
      fetchSummary,
      openModal,
      closeModal,
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(ShippingSettingsTab);
