import React, { useEffect } from 'react';
import { Box, Text, Divider, Heading } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { fetchOauthConnectedApplications as fetchOauthConnectedApplicationsfn } from 'merchant/reducers/applications';
import { fetchGenericFeatureStatus } from 'merchant/reducers/genericFeature';
import { FEATURE_WHATSAPP_PL } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/constants';

import ActiveSetup from './components/ActiveSetup';
import InitiateSetup from './components/InitiateSetup';
import Shimmer from './components/Shimmer';
import { getApplicationStatus, whatsappAccountSetupAnalyticsTrack } from './utils';

const WhatsAppSetup = ({
  user,
  applications,
  fetchOauthConnectedApplications,
  fetchGenericFeatureStatus,
}): JSX.Element => {
  useEffect(() => {
    fetchOauthConnectedApplications();
    fetchGenericFeatureStatus(user.id, FEATURE_WHATSAPP_PL);
    whatsappAccountSetupAnalyticsTrack({
      objectName: 'Whatsapp Account Setup Page',
      actionName: 'Displayed',
    });
  }, []);

  const { connectedAppsloading: isConnectedAppsloading, tokens } = applications;

  const { isActive, businessProvider } = getApplicationStatus({ tokens });
  return (
    <Box
      display="flex"
      flexDirection="column"
      gap="spacing.5"
      padding={{ base: 'spacing.7', m: 'spacing.0' }}
    >
      <Box display="flex" flexDirection="column" gap={{ base: '10px', m: 'spacing.2' }}>
        <Heading weight="semibold" size="medium">
          Whatsapp Account Set-up
        </Heading>
        <Text size="medium" color="surface.text.gray.subtle">
          To send payment requests via WhatsApp, please set-up your WABA account with Razorpay
        </Text>
      </Box>
      <Divider />
      {isConnectedAppsloading ? (
        <Shimmer />
      ) : isActive ? (
        <ActiveSetup businessProvider={businessProvider} />
      ) : (
        <InitiateSetup />
      )}
    </Box>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  applications: state.applications,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      fetchOauthConnectedApplications: fetchOauthConnectedApplicationsfn,
      fetchGenericFeatureStatus,
    },
    dispatch,
  );
};
export default connect(mapStateToProps, mapDispatchToProps)(WhatsAppSetup);
