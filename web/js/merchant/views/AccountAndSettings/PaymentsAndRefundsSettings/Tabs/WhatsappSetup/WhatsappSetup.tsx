import React, { useEffect } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { Box, Heading, Text, Divider } from '@razorpay/blade/components';
import InitiateSetup from './components/InitiateSetup';
import ActiveSetup from './components/ActiveSetup';
import { fetchOauthConnectedApplications as fetchOauthConnectedApplicationsfn } from 'merchant/reducers/applications';
import Shimmer from './components/Shimmer';
import { getApplicationStatus, whatsappAccountSetupAnalyticsTrack } from './utils';

const WhatsAppSetup = ({ applications, fetchOauthConnectedApplications }): JSX.Element => {
  useEffect(() => {
    fetchOauthConnectedApplications();
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
        <Heading size="large" weight="bold">
          Whatsapp Account Set-up
        </Heading>
        <Text size="medium" type="subtle">
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
  applications: state.applications,
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    { fetchOauthConnectedApplications: fetchOauthConnectedApplicationsfn },
    dispatch,
  );
};
export default connect(mapStateToProps, mapDispatchToProps)(WhatsAppSetup);
