import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';

import { Box, Button, CheckIcon, useToast } from '@razorpay/blade/components';
import {
  CheckoutFrame,
  FrameContainer,
  PreviewBadge,
  ScrollablePreview,
  PreviewWrapper,
} from 'merchant/views/MagicCheckout/Settings/containers/SSO/styled';
import { SSOPreviewContext } from 'merchant/views/MagicCheckout/Settings/containers/SSO/components/tabs/Customise/context/context';
import { useSSOContext } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/index';
import { getSSOConfigPayload } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/helpers';
import {
  createInitialOptions,
  createNewSSOOption,
  createSSOMessageListener,
  getUpdatedSSODisplayText,
  initSSO,
} from '@dashboards/payments/views/MagicCheckout/Settings/containers/SSO/components/tabs/Customise/helpers';
import { saveSSOSettings } from 'merchant/views/MagicCheckout/Settings/containers/SSO/api';
import WidgetCustomisationButtons from 'merchant/views/MagicCheckout/Settings/containers/SSO/components/common/WidgetCustomisationButtons';
import SSOPreviewButtons from 'merchant/views/MagicCheckout/Settings/containers/SSO/components/common/PreviewButtons';
import { SSO_IFRAME_URL } from 'merchant/views/MagicCheckout/Settings/containers/SSO/constants';

const Customise = () => {
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [isDesktopPreview, setIsDesktopPreview] = useState<boolean>(false);
  const [shouldShowPreview, setShouldShowPreview] = useState<boolean>(false);
  const [isEditable, setIsEditable] = useState<boolean>(true);

  const { show } = useToast();
  const {
    isSSOEnabled,
    ssoWidget,
    ssoSettings,
    apiKey,
    merchantId,
    dashboardView,
    updateSSOWidgetCarousel,
  } = useSSOContext();

  const updateCheckout = useRef<{
    update: (value: Record<string, unknown>) => void;
    removeListener: () => void;
  } | null>(null);

  const messageListenerRef = useRef<{ cleanup: () => void } | null>(null);

  const displayTextConfigRef = useRef({
    isSSOEnabled,
    ssoWidget,
    ssoSettings,
  });

  useEffect(() => {
    displayTextConfigRef.current = {
      isSSOEnabled,
      ssoWidget,
      ssoSettings,
    };
  }, [isSSOEnabled, ssoWidget, ssoSettings]);

  useEffect(() => {
    // Cleanup the previous message listener
    messageListenerRef.current?.cleanup?.();

    // Set up a new listener with the latest state
    messageListenerRef.current = createSSOMessageListener('sso-frame', (data) => {
      const updatedDisplayTextConfig = getUpdatedSSODisplayText(
        displayTextConfigRef.current, // Always get the latest state
        data,
      );
      updateSSOWidgetCarousel(updatedDisplayTextConfig);
    });

    // removes the listener when the component unmounts
    return () => {
      messageListenerRef.current?.cleanup?.();
      updateCheckout.current?.removeListener?.();
    };
  }, []);

  // initialize the SSO iframe
  const init = (node: HTMLIFrameElement) => {
    if (node && !updateCheckout.current) {
      initSSO(node).then((result) => {
        updateCheckout.current = result;
        setShouldShowPreview(true);
      });
    }
  };

  const handleSave = useCallback(async () => {
    try {
      setIsLoading(true);
      const storeConfig = { isSSOEnabled, ssoSettings, ssoWidget };
      const payload = getSSOConfigPayload(storeConfig, merchantId);
      await saveSSOSettings(payload).then(() => {
        show({
          type: 'informational',
          content: 'configs saved successfully',
          color: 'positive',
        });
      });
    } catch (error) {
      show({
        type: 'informational',
        content: 'configs save failed',
        color: 'negative',
      });
    } finally {
      setIsLoading(false);
    }
  }, [isSSOEnabled, ssoSettings, ssoWidget]);

  useEffect(() => {
    const payload = getSSOConfigPayload({ isSSOEnabled, ssoSettings, ssoWidget }, merchantId);
    // update the SSO options
    const newOptions = createNewSSOOption(
      createInitialOptions(apiKey, true, !isDesktopPreview),
      {
        sso_widget: payload.configs?.sso_config.sso_widget,
        sso_settings: payload.configs?.sso_config.sso_settings,
      },
      isDesktopPreview,
      isEditable,
      apiKey,
    );
    if (updateCheckout?.current?.update) {
      updateCheckout.current?.update(newOptions);
    }
  }, [
    ssoWidget.backgroundColor,
    ssoWidget.buttonColor,
    ssoWidget.fontFamily,
    ssoSettings,
    isDesktopPreview,
    shouldShowPreview,
    isEditable,
    apiKey,
  ]);

  return (
    <SSOPreviewContext.Provider value={{ isDesktopPreview, setIsDesktopPreview }}>
      <PreviewWrapper>
        <PreviewBadge isEditable={isEditable}>{isEditable ? 'Edit' : 'Live'} Mode</PreviewBadge>
        <ScrollablePreview isDesktopPreview={isDesktopPreview}>
          <FrameContainer isDesktopPreview={isDesktopPreview}>
            {apiKey ? (
              <CheckoutFrame
                ref={init}
                tabIndex="-1"
                src={`${SSO_IFRAME_URL}${apiKey}`}
                isDesktopPreview={isDesktopPreview}
                data-testid="sso-iframe"
              />
            ) : (
              'User does not have access'
            )}
          </FrameContainer>
        </ScrollablePreview>

        <WidgetCustomisationButtons isEditable={isEditable} setIsEditable={setIsEditable} />

        <Box
          display="flex"
          gap="36px"
          alignItems="center"
          alignSelf="flex-end"
          padding="spacing.4"
          marginRight="spacing.4"
        >
          <SSOPreviewButtons
            isDesktopPreview={isDesktopPreview}
            setIsDesktopPreview={setIsDesktopPreview}
          />
          <Button
            onClick={handleSave}
            variant="primary"
            icon={CheckIcon}
            iconPosition="left"
            size="medium"
            isLoading={isLoading}
          >
            Save
          </Button>
        </Box>
      </PreviewWrapper>
    </SSOPreviewContext.Provider>
  );
};

export default Customise;
