import React, { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';

import {
  Heading,
  Box,
  Text,
  Spinner,
  useToast,
  Alert,
  Avatar,
  CloseIcon,
} from '@razorpay/blade/components';
import VideoGuide from 'merchant/views/MagicCheckout/Settings/containers/SSO/components/VideoGuide';
import { fetchSSOStatus } from 'merchant/views/MagicCheckout/Settings/containers/SSO/api';
import { useSSOContext } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/index';
import { updateSSOStore } from 'merchant/views/MagicCheckout/Settings/containers/SSO/context/helpers';
import { fetchKeys } from 'merchant/reducers/keys';
import { SSO_FIRST_TIME_USER_CONFIG } from 'merchant/views/MagicCheckout/Settings/containers/SSO/constants';
import { SSOConfigs } from 'merchant/views/MagicCheckout/Settings/containers/SSO/types';
import { SSOContainer } from 'merchant/views/MagicCheckout/Settings/containers/SSO/styled';
import SSO from 'merchant/views/MagicCheckout/Settings/containers/SSO/components/TabsContainer';
import Toggle from 'merchant/views/MagicCheckout/Settings/containers/SSO/components/common/Toggle';
import HeaderDescriptions from './common/HeaderDescriptions';

const SSOHome: React.FC<{
  apiKey: string;
  mode: string;
  user: Record<string, unknown>;
  merchantId: string;
  dashboardView: string;
  fetchKeys: (payload: { mode: string }, hasKeyAccess: boolean) => void;
}> = ({ apiKey, mode, user, merchantId, dashboardView, fetchKeys }) => {
  const [isLoading, setIsLoading] = useState<boolean>(true);
  const [isError, setIsError] = useState<boolean>(false);

  const ssoContext = useSSOContext();
  const { isSSOEnabled, updateIsSSOEnabled, setApiKey, setMerchantId, setDashboardView, setMode } =
    ssoContext;
  const { show } = useToast();

  const fetchSSOSettings = useCallback(async () => {
    try {
      setIsLoading(true);
      const res = await fetchSSOStatus();
      if (res.success && res?.data?.configs?.sso_config?.sso_enabled) {
        updateIsSSOEnabled(true);
        show({
          type: 'informational',
          content: 'sso config fetched successfully',
          color: 'positive',
        });
      }
      return res;
    } catch (error) {
      setIsError(true);
      show({
        type: 'informational',
        content: 'sso config fetch failed',
        color: 'negative',
      });
    } finally {
      setIsLoading(false);
    }
  }, [isSSOEnabled]);

  useEffect(() => {
    // set the merchant id in sso context
    if (merchantId) {
      setMerchantId(merchantId);
    }
  }, [merchantId]);

  useEffect(() => {
    if (dashboardView) {
      setDashboardView(dashboardView);
    }
  }, [dashboardView]);

  useEffect(() => {
    if (mode) {
      setMode(mode);
    }
    if (apiKey) {
      setApiKey(apiKey);
    }
    if (mode && !apiKey) {
      fetchKeys({ mode }, user?.['has_key_access'] as boolean);
    }
  }, [apiKey, mode, fetchKeys]);

  useEffect(() => {
    fetchSSOSettings()
      .then((response) => {
        const updatedConfig = (
          response?.success && response?.data?.configs?.sso_config
            ? response.data?.configs?.sso_config
            : SSO_FIRST_TIME_USER_CONFIG
        ) as SSOConfigs;
        updateSSOStore(updatedConfig, ssoContext);
      })
      .catch(() => {
        show({
          type: 'informational',
          content: 'sso config update failed',
          color: 'negative',
        });
      });
  }, []);

  const renderContent = () => {
    if (isLoading) {
      return (
        <Box
          as="section"
          height="90vh"
          width="100%"
          display="flex"
          alignItems="center"
          justifyContent="center"
        >
          <Spinner color="primary" accessibilityLabel="sso-loader" />
        </Box>
      );
    }
    if (isError) {
      return (
        <Box
          flexDirection="column"
          display="flex"
          alignItems="center"
          justifyContent="center"
          width="100%"
          gap="spacing.2"
        >
          <Avatar size="large" icon={CloseIcon} color="negative" />
          <Box
            flexDirection="column"
            display="flex"
            alignItems="center"
            justifyContent="center"
            width="100%"
          >
            <Heading>We are facing some technical issues</Heading>
            <Text color="surface.text.gray.muted" size="small">
              Please try again
            </Text>
          </Box>
        </Box>
      );
    }
    return (
      <>
        <Alert
          position="top"
          color="notice"
          description={<HeaderDescriptions />}
          emphasis="subtle"
          isDismissible={false}
          title="Mandatory Configs on Shopify"
          isFullWidth
        />
        <Box padding="spacing.6" paddingBottom="spacing.0" marginBottom="spacing.0">
          <Heading size="medium">Login with Razorpay Setup</Heading>
          <Text color="surface.text.gray.muted" marginTop="spacing.4">
            Set up easy & secure login into your store with single sign-in for your customers across
            the Razorpay network
          </Text>
          <Toggle />
        </Box>

        {isSSOEnabled ? <SSO /> : <VideoGuide />}
      </>
    );
  };

  return <SSOContainer>{renderContent()}</SSOContainer>;
};

const mapActionsToProps = {
  fetchKeys,
};

const mapStateToProps = (state: any) => ({
  apiKey: state.keys?.keys?.[0]?.id ?? null,
  mode: state.session.mode,
  user: state.session.user,
  merchantId: state.session.user?.current,
  dashboardView: state.magicCheckout.dashboard_view,
});

export default connect(mapStateToProps, mapActionsToProps)(SSOHome);
