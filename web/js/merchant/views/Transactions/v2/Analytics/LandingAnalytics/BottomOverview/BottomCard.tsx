import React, { useState } from 'react';
import {
  Amount,
  Box,
  Card,
  CardBody,
  ChevronRightIcon,
  IconButton,
  InfoIcon,
  Text,
  Heading,
  Tooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';
import noop from 'lodash/noop';
import { withRouter } from 'react-router-dom';

import { paiseToRupees } from 'common/utils/rzp-utils';
import { CardShimmer } from 'merchant/views/Transactions/v2/Analytics/components/Shimmer';
import {
  BottomCardWrapper,
  ViewDetailsPrefix,
} from 'merchant/views/Transactions/v2/Analytics/styled';
import { BottomOverviewCardProps } from 'merchant/views/Transactions/v2/Analytics/types';
import {
  LandingPageAnalyticsToolTip,
  cardLink,
} from 'merchant/views/Transactions/v2/Analytics/utils';
import { TooltipWrapper } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/styled';
import { track } from 'merchant/views/Transactions/v2/common/tracking';

import CardFooter from './CardFooter';
import CardIcon from './CardIcon';

const BottomOverviewCard = ({
  currency,
  history,
  data,
  footerValues,
  location,
  durationOption,
}: BottomOverviewCardProps): JSX.Element | null => {
  const [isHover, setIsHover] = useState(false);
  const { name, loading: isLoading, value, isAmount, failed: isFailed } = data;
  const goToEntityPage = () => {
    track({
      objectName: `${name} Tab`,
      properties: { overviewDate: durationOption.title, section: 'Overview' },
    });
    sessionStorage.setItem('overviewDuration', JSON.stringify(durationOption));
    history.push(cardLink[name], { prevPath: location.pathname });
  };
  if (isLoading) {
    return <CardShimmer />;
  }
  return (
    <BottomCardWrapper onClick={goToEntityPage}>
      <Box onMouseEnter={() => setIsHover(true)} onMouseLeave={() => setIsHover(false)}>
        <Box flex="1">
          <Card
            surfaceLevel={isHover ? 3 : 2}
            padding="spacing.5"
            marginY="spacing.2"
            elevation="none"
            display="flex"
          >
            <CardBody>
              <Box minHeight="90px">
                <Box
                  display="flex"
                  gap="spacing.2"
                  alignItems="center"
                  justifyContent="space-between"
                  minWidth="220px"
                >
                  <Box display="flex" gap="spacing.2" alignItems="center">
                    <CardIcon name={name} />
                    <Text type="subtle" weight="bold" contrast="low" size="medium">
                      {name}
                    </Text>
                    <TooltipWrapper
                      onClick={(e) => {
                        e.stopPropagation();
                      }}
                    >
                      <Tooltip content={LandingPageAnalyticsToolTip[name]} placement="top">
                        <TooltipInteractiveWrapper>
                          <InfoIcon color="feedback.icon.neutral.lowContrast" size="small" />
                        </TooltipInteractiveWrapper>
                      </Tooltip>
                    </TooltipWrapper>
                  </Box>
                  <ViewDetailsPrefix>
                    <IconButton
                      accessibilityLabel={`view-${name}-details`}
                      icon={ChevronRightIcon}
                      size="large"
                      onClick={noop}
                    />
                  </ViewDetailsPrefix>
                </Box>
                <Box marginY="spacing.3" marginX="spacing.5">
                  {isFailed ? null : isAmount ? (
                    <Amount
                      isAffixSubtle={true}
                      suffix="decimals"
                      currency={currency}
                      size="title-small"
                      value={paiseToRupees(value)}
                    />
                  ) : (
                    <Heading
                      color="surface.text.normal.lowContrast"
                      size="large"
                      marginLeft="spacing.2"
                    >
                      {value}
                    </Heading>
                  )}
                </Box>
                <Box marginX="spacing.6">
                  {isFailed ? (
                    <Box display="flex" flexDirection="row" gap="spacing.3">
                      <Text>Couldn&apos;t be loaded</Text>
                    </Box>
                  ) : (
                    <CardFooter name={name} values={footerValues} />
                  )}
                </Box>
              </Box>
            </CardBody>
          </Card>
        </Box>
      </Box>
    </BottomCardWrapper>
  );
};

export default withRouter(BottomOverviewCard);
