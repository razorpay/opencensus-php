import React from 'react';
import { Box, Text, Heading, ArrowLeftIcon } from '@razorpay/blade/components';
import ClientInfoCardBG from 'assets/partner-dashboard/pos-order-list/client-info-card.svg';
import { connect } from 'react-redux';
import { useLocation, useNavigate, useNavigationType } from 'react-router-dom';
import styled from 'styled-components';

import OrderList from './OrderList';
import { PosDeviceStoreProvider } from './providers';
import { ScrollObserverProvider } from './ScrollObserver';

import ClientInfoCard from './ClientInfoCard';
import { getSubmerchantIdFromPath } from './utils';

const StyledBackContainer = styled.div`
  cursor: pointer;
`;
// Note: This component is rendered via the fullPageViewsMap to hide
// the Navlinks sidebar. It is expected to be opened in a new tab.
const ClientPOSOrderList = ({ user }): JSX.Element => {
  const location = useLocation();
  const submerchantId = getSubmerchantIdFromPath(location.pathname);
  const navigate = useNavigate();
  const navigationType = useNavigationType();

  const isHistoryBackAvailable = navigationType === 'PUSH';
  const handleBackToDashboardClick = () => {
    navigate(-1);
  };
  return (
    <Box>
      <Box
        width="100%"
        backgroundImage={`url("${ClientInfoCardBG}")`}
        backgroundColor="surface.background.cloud.intense"
        backgroundSize="cover"
        backgroundRepeat="no-repeat"
        backgroundPosition="top right"
      >
        <Box
          paddingLeft="40px"
          paddingRight="25px"
          paddingTop={{ base: '150px', l: isHistoryBackAvailable ? '100px' : '120px' }}
          paddingBottom="60px"
          display="flex"
          flexDirection="column"
        >
          {isHistoryBackAvailable ? (
            <StyledBackContainer onClick={handleBackToDashboardClick}>
              <Box display="inline-block" position="relative" top="2px">
                <ArrowLeftIcon color="surface.icon.staticWhite.normal" size="medium" />
              </Box>
              <Text
                marginLeft="spacing.3"
                display="inline-block"
                color="surface.text.staticWhite.normal"
                weight="semibold"
              >
                Back to Dashboard
              </Text>
            </StyledBackContainer>
          ) : null}
          <Box marginTop="spacing.8">
            <Heading color="surface.text.staticWhite.normal" size="large">
              Client Orders
            </Heading>
          </Box>
          <Box marginTop="spacing.9" minHeight="90px">
            <ClientInfoCard submerchantId={submerchantId} />
          </Box>
        </Box>
      </Box>
      <Box zIndex={2} position="relative" marginTop="-60px">
        <ScrollObserverProvider>
          <PosDeviceStoreProvider isRenderedFromPartnerRoute user={user}>
            <OrderList />
          </PosDeviceStoreProvider>
        </ScrollObserverProvider>
      </Box>
    </Box>
  );
};

export default connect(
  (state) => ({
    user: state.session.user,
  }),
  null,
)(ClientPOSOrderList);
