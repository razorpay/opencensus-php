import React from 'react';
import { Box, Card, CardBody, Divider } from '@razorpay/blade/components';
import { CurrencyCodeType } from '@razorpay/i18nify-js/currency';
import { connect } from 'react-redux';

import { BorderWrapper } from 'merchant/views/Transactions/v2/Analytics/styled';
import { AnalyticsBoilerPlateProps } from 'merchant/views/Transactions/v2/Analytics/types';
import { ScrollableContainer } from 'merchant/views/Transactions/v2/common/styled';

import CardInfo from './components/CardInfo';

const AnalyticsBoilerPlate = ({ isLoading, isMobile, data, user }: AnalyticsBoilerPlateProps) => {
  const currency = user.merchant?.currency as CurrencyCodeType;
  const { lead, trail } = data;
  return isMobile ? (
    <Box display="flex" flexDirection="column" gap="spacing.4" marginTop="spacing.4">
      <Card elevation="none" backgroundColor="surface.background.gray.intense" padding="spacing.5">
        <CardBody>
          <CardInfo
            {...lead}
            isLoading={isLoading}
            currency={currency}
            isLeader={true}
            isMobile={isMobile}
          />
        </CardBody>
      </Card>
      <ScrollableContainer>
        <Box display="flex" flexDirection="row" justifyContent="space-between" gap="spacing.4">
          {trail.map((element) => (
            <Box flex={1} key={element.title}>
              <Card
                backgroundColor="surface.background.gray.moderate"
                elevation="none"
                padding="spacing.5"
              >
                <CardBody>
                  <Box
                    display="flex"
                    flexDirection="column"
                    gap="spacing.2"
                    justifyContent="space-between"
                    minWidth="200px"
                  >
                    <CardInfo
                      {...element}
                      isLoading={isLoading}
                      currency={currency}
                      isMobile={isMobile}
                    />
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
        backgroundColor="surface.background.gray.moderate"
      >
        <Box flex="1" padding="spacing.5" gap="spacing.2">
          <CardInfo
            {...lead}
            isLoading={isLoading}
            currency={currency}
            isLeader={true}
            isMobile={isMobile}
          />
        </Box>
        <Box
          flex={trail.length}
          padding="spacing.5"
          backgroundColor="surface.background.gray.intense"
          display="flex"
          justifyContent="space-between"
          gap="spacing.5"
          minHeight="150px"
          alignItems="center"
        >
          {trail.map((element, index) => (
            <>
              <Box flexGrow={1}>
                <CardInfo
                  {...element}
                  isLoading={isLoading}
                  currency={currency}
                  isMobile={isMobile}
                />
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
