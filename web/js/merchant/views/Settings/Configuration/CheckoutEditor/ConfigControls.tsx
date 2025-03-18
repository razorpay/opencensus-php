import React, { useEffect, useState } from 'react';
import { Box, Button, Divider, useTheme } from '@razorpay/blade/components';

import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/createContext';
import { PreventDiscardChangesModal } from './PaymentConfiguration/ConfigurationDetails/PreventDiscardChangesModal';
import { SlideUp } from './components/styles';
import { SavePaymentConfig } from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/ConfigurationDetails/SavePaymentConfig';
import { CHECKOUT_EDITOR_FIELDS } from './context';
import sendToSegment from './track';

const ConfigControls = () => {
  const {
    isSaving,
    isValueModified,
    isPaymentConfigChanged,
    values,
    handleDiscardAllChanges,
    handleSave,
  } = useCheckoutEditor();

  const { theme } = useTheme();
  const [shouldRender, setShouldRender] = useState(false);
  const [isVisible, setIsVisible] = useState(false);
  const [isSavePaymentConfigModalOpen, setIsSavePaymentConfigModalOpen] = useState(false);
  const [isPreventDiscardChangesModalOpen, setIsPreventDiscardChangesModalOpen] = useState(false);

  function handleDiscardChangesContinue() {
    setIsPreventDiscardChangesModalOpen(false);
    handleDiscardAllChanges();
    sendToSegment(
      'discard changes continue',
      'clicked',
      {},
      'Checkout Settings',
      'Checkout Editor',
    );
  }
  let modalType = '';
  const isConfigSetAsDefaultInitially =
    values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG]?.isConfigSetAsDefaultInitially ?? false;
  const isCurrentPaymentConfigDefault =
    values[CHECKOUT_EDITOR_FIELDS.SELECTED_PAYMENT_CONFIG]?.is_default ?? false;
  if (isCurrentPaymentConfigDefault && !isConfigSetAsDefaultInitially) {
    modalType = 'default';
  } else if (!isCurrentPaymentConfigDefault && !isConfigSetAsDefaultInitially) {
    modalType = 'non-default';
  } else {
    modalType = '';
  }

  // this is done to support flying-in/out animation
  // we only want to hide the component when animation is complete
  // by hide, we intend to remove the component from the DOM itself
  useEffect(() => {
    let timerID;
    clearTimeout(timerID);
    if (isValueModified) {
      // first render and then animate(fly-in)
      setShouldRender(true);
      timerID = setTimeout(() => {
        setIsVisible(true);
      }, 50);
    } else {
      // first animatie(fly-out) and then hide
      setIsVisible(false);
      timerID = setTimeout(() => {
        setShouldRender(false);
      }, theme.motion.duration.gentle);
    }
  }, [isValueModified, theme.motion.duration.gentle]);

  return shouldRender ? (
    <>
      <SlideUp isVisible={isVisible}>
        <Box display="flex" flexDirection="column" alignItems="flex-end" flex="1" gap="spacing.4">
          <Divider
            dividerStyle="solid"
            thickness="thin"
            variant="muted"
            orientation="horizontal"
            width="100%"
          />
          <Box display="flex" gap="spacing.5">
            <Button
              variant="tertiary"
              isDisabled={!isValueModified || isSaving}
              onClick={() => setIsPreventDiscardChangesModalOpen(true)}
            >
              Discard all changes
            </Button>
            <Button
              isDisabled={!isValueModified || isSaving}
              isLoading={isSaving}
              onClick={() => {
                if (isPaymentConfigChanged) {
                  if (modalType === '') {
                    handleSave();
                  } else {
                    setIsSavePaymentConfigModalOpen(true);
                  }
                } else {
                  handleSave();
                }
              }}
            >
              Save all changes
            </Button>
          </Box>
        </Box>
      </SlideUp>
      <SavePaymentConfig
        isOpen={isSavePaymentConfigModalOpen}
        onClose={() => setIsSavePaymentConfigModalOpen(false)}
        isSaving={isSaving}
        modalType={modalType}
      />
      <PreventDiscardChangesModal
        isOpen={isPreventDiscardChangesModalOpen}
        title="Are you sure you want to discard all changes?"
        content="This action will discard your progress. Are you sure you want to continue?"
        onClose={() => setIsPreventDiscardChangesModalOpen(false)}
        onDiscard={handleDiscardChangesContinue}
      />
    </>
  ) : null;
};

export default ConfigControls;
