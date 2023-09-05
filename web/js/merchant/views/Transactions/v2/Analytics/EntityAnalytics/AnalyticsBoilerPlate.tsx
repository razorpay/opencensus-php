import React from 'react';
import { Box, Card, CardBody, Divider } from '@razorpay/blade/components';
import { connect } from 'react-redux';

import {
  BorderWrapper,
  ScrollableContainer,
} from 'merchant/views/Transactions/v2/Analytics/styled';
import { AnalyticsBoilerPlateProps } from 'merchant/views/Transactions/v2/Analytics/types';
import { Currency } from 'merchant/views/Transactions/v2/Payments/types';

import CardInfo from './components/CardInfo';

const AnalyticsBoilerPlate = ({ isLoading, isMobile, data, user }: AnalyticsBoilerPlateProps) => {
  const currency = user.merchant?.currency as Currency;
  const { lead, trail } = data;
  return isMobile ? (
    <Box display="flex" flexDirection="column" gap="spacing.4" marginTop="spacing.4">
      <Card elevation="none" surfaceLevel={3} padding="spacing.5">
        <CardBody>
          <CardInfo {...lead} isLoading={isLoading} currency={currency} isLeader={true} />
        </CardBody>
      </Card>
      <ScrollableContainer>
        <Box display="flex" flexDirection="row" justifyContent="space-between" gap="spacing.4">
          {trail.map((element) => (
            <Box flex={1} key={element.title}>
              <Card surfaceLevel={2} elevation="none" padding="spacing.5">
                <CardBody>
                  <Box
                    display="flex"
                    flexDirection="column"
                    gap="spacing.2"
                    justifyContent="space-between"
                    minWidth="200px"
                  >
                    <CardInfo {...element} isLoading={isLoading} currency={currency} />
                  </Box>
                </CardBody>
              </Card>
            </Box>
          ))}
        </Box>
      </ScrollableContainer>
    </Box>
  ) : (
    <BorderWrapper>
      <Box
        display="flex"
        flexDirection="row"
        alignItems="center"
        backgroundColor="surface.background.level3.lowContrast"
      >
        <Box flex="1" padding="spacing.5" gap="spacing.2">
          <CardInfo {...lead} isLoading={isLoading} currency={currency} isLeader={true} />
        </Box>
        <Box
          flex={trail.length}
          padding="spacing.5"
          backgroundColor="surface.background.level2.lowContrast"
          display="flex"
          justifyContent="space-between"
          gap="spacing.5"
          minHeight="150px"
          alignItems="center"
        >
          {trail.map((element, index) => (
            <>
              <Box flexGrow={1}>
                <CardInfo {...element} isLoading={isLoading} currency={currency} />
              </Box>
              {!(index === trail.length - 1) && <Divider orientation="vertical" />}
            </>
          ))}
        </Box>
      </Box>
    </BorderWrapper>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});
export default connect(mapStateToProps)(AnalyticsBoilerPlate);
