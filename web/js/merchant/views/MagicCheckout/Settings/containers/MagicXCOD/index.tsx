import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { useNavigate } from 'react-router-dom';

import {
  Text,
  Heading,
  Box,
  Switch,
  AlertTriangleIcon,
  Link,
  Modal,
  ModalHeader,
  ModalBody,
  ModalFooter,
  Button,
} from '@razorpay/blade/components';
import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { DisplayNotificationTxt } from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import BasicCOD from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/Containers/BasicCOD';

import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { showNotification } from 'merchant_common/reducers/notifications';
import { fetchSummary as fetchShippingProfiles } from 'merchant/reducers/magicCheckout/shippingEngine/action';
import { checkMagicConfigurationFlow } from 'merchant/views/MagicCheckout/utils/Configuration';

import { FormContextProvider } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/Context';

import {
  SETUP_MAGICX_ROUTE,
  CONFIRMATION_MODAL_OBJECT,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/constants';
import { SWITCH_TEXTS } from 'merchant/views/MagicCheckout/Settings/constants';
import { COD_ENGINE_TYPES } from 'merchant/views/MagicCheckout/CODSettings/constants';

import { ToggleCODPayload } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/types';

const MagicXCOD = ({ settings, updateMagicSettings, showNotification, fetchShippingProfiles }) => {
  const { rcod = {}, cod_intelligence, sopc_metafields, platform, shop_id } = settings;
  const [isCODPaymentEnabled, setCODPaymentStatus] = useState(rcod?.enabled);
  const [isCODIntelligenceEnabled, setCODIntelligenceStatus] = useState(Boolean(cod_intelligence));
  const [isConfirmationModalOpen, setIsConfirmationModalOpen] = useState(false);
  const [isConfirmationModalLoading, setIsConfirmationModalLoading] = useState(false);
  const [confirmationModalConfigs, setConfirmationModalConfigs] =
    useState(CONFIRMATION_MODAL_OBJECT);
  const navigate = useNavigate();

  useEffect(() => {
    fetchShippingProfiles();
  }, []);

  const notify = (type, message) =>
    showNotification({ type, message: <DisplayNotificationTxt notificationTxt={message} /> });

  const toggleCODSettings = () => {
    const params: ToggleCODPayload = {
      platform,
      shop_id,
      rcod: {
        enabled: !isCODPaymentEnabled,
      },
    };

    if (params.rcod.enabled) {
      params.rcod.configs = {
        cod_engine_type: COD_ENGINE_TYPES.SLAB_ELIGIBILITY,
      };
    }

    updateMagicSettings(params, false)
      .then(() => {
        notify('success', 'COD Settings saved successfully');
        setCODPaymentStatus((isCODPaymentEnabled) => !isCODPaymentEnabled);
      })
      .catch((err) => {
        notify('error', `${err?.errors[0] || 'Something went wrong'}`);
      })
      .finally(() => {
        setIsConfirmationModalLoading(false);
        setIsConfirmationModalOpen(false);
      });
  };

  const toggleCODIntelligence = () => {
    const modalAction = isCODIntelligenceEnabled ? 'disabled' : 'enabled';
    const params = {
      platform,
      cod_intelligence: !isCODIntelligenceEnabled,
      manual_control_cod_order: false,
      shop_id,
    };
    updateMagicSettings(params, false)
      .then(() => {
        notify('success', `COD Intelligence ${modalAction} successfully`);
        setCODIntelligenceStatus((isCODIntelligenceEnabled) => !isCODIntelligenceEnabled);
      })
      .finally(() => {
        setIsConfirmationModalLoading(false);
        setIsConfirmationModalOpen(false);
      });
  };

  const handleToggle = (e) => {
    const {
      isChecked,
      event: {
        target: { name },
      },
    } = e;
    const switchState = isChecked ? 'enable' : 'disable';
    const { header, desc, secondaryCtaLabel, primaryCtaLabel } = SWITCH_TEXTS[switchState]?.[name];
    setIsConfirmationModalOpen(true);
    setConfirmationModalConfigs({
      name,
      header,
      desc,
      primaryCtaLabel,
      secondaryCtaLabel,
    });
  };

  return (
    <ErrorBoundary team={Teams?.MAGIC_CHECKOUT} rank={Ranks.P0} resetOnProps>
      <div className="cod-content-wrapper">
        <div className="cod-content">
          <div className="text-container">
            <Heading size="medium">Cash on Delivery Setup</Heading>
          </div>
          <Box display="flex" marginY="spacing.6">
            <Box
              display="flex"
              justifyContent="space-between"
              alignItems="center"
              borderWidth="thinner"
              borderColor="surface.border.gray.subtle"
              padding="spacing.5"
              borderTopLeftRadius="medium"
              borderBottomLeftRadius="medium"
            >
              <Text marginRight="spacing.10" marginLeft="spacing.3" weight="semibold">
                Enable COD as payment option
              </Text>
              <Switch
                accessibilityLabel="magicx cod"
                marginX="spacing.3"
                isChecked={isCODPaymentEnabled}
                onChange={handleToggle}
                name="codSettings"
              />
            </Box>
            {isCODPaymentEnabled && (
              <Box
                borderLeftWidth="none"
                borderWidth="thinner"
                borderColor="surface.border.gray.subtle"
                padding="spacing.5"
                maxWidth="40%"
                borderTopRightRadius="medium"
                borderBottomRightRadius="medium"
              >
                <Box display="flex" justifyContent="space-between" alignItems="center">
                  <Text marginRight="spacing.10" marginLeft="spacing.3" weight="semibold">
                    Enable RTO Intelligence
                  </Text>
                  <Switch
                    accessibilityLabel="magicx cod intelligence"
                    marginX="spacing.3"
                    isChecked={isCODIntelligenceEnabled}
                    onChange={handleToggle}
                    name="codIntelligence"
                  />
                </Box>
                {isCODIntelligenceEnabled && !(sopc_metafields?.status === 'live') && (
                  <Box display="flex" marginBottom="-15px" alignItems="center">
                    <AlertTriangleIcon
                      color="feedback.icon.notice.intense"
                      marginX="spacing.3"
                      size="small"
                    />
                    <Text variant="body" size="small">
                      <Link
                        onClick={() =>
                          navigate(
                            //Support to render on Dashboard Full Page View mode
                            checkMagicConfigurationFlow()
                              ? `/configuration${SETUP_MAGICX_ROUTE}`
                              : SETUP_MAGICX_ROUTE,
                          )
                        }
                        marginRight="spacing.2"
                        size="small"
                      >
                        Activate
                      </Link>
                      Checkout360 for this to work.
                    </Text>
                  </Box>
                )}
              </Box>
            )}
            <Modal
              isOpen={isConfirmationModalOpen}
              size="small"
              onDismiss={() => setIsConfirmationModalOpen(false)}
            >
              <ModalHeader title={confirmationModalConfigs?.header} />
              <ModalBody>
                <Text>{confirmationModalConfigs?.desc}</Text>
              </ModalBody>
              <ModalFooter>
                <Box display="flex" gap="spacing.3" justifyContent="flex-end" width="100%">
                  <Button
                    isDisabled={isConfirmationModalLoading}
                    variant="secondary"
                    onClick={() => setIsConfirmationModalOpen(false)}
                  >
                    {confirmationModalConfigs?.secondaryCtaLabel}
                  </Button>
                  <Button
                    testID="confirm-button"
                    isLoading={isConfirmationModalLoading}
                    isDisabled={isConfirmationModalLoading}
                    onClick={() => {
                      setIsConfirmationModalLoading(true);
                      if (confirmationModalConfigs?.name === 'codIntelligence')
                        toggleCODIntelligence();
                      else toggleCODSettings();
                    }}
                  >
                    {confirmationModalConfigs?.primaryCtaLabel}
                  </Button>
                </Box>
              </ModalFooter>
            </Modal>
          </Box>
          <Box>
            <FormContextProvider>
              <BasicCOD />
            </FormContextProvider>
          </Box>
        </div>
      </div>
    </ErrorBoundary>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      updateMagicSettings,
      fetchShippingProfiles,
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(MagicXCOD);
