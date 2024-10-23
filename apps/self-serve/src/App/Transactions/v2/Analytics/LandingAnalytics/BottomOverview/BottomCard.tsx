import {
  Amount,
  Box,
  Card,
  CardBody,
  ChevronRightIcon,
  Heading,
  IconButton,
  InfoIcon,
  Text,
  Tooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';
import { formatNumber } from '@razorpay/i18nify-js/currency';
import noop from 'lodash/noop';
import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';

import { paiseToRupees } from '@dashboard/shared-utils/rzp-utils';
import { CardShimmer } from 'apps/self-serve/src/App/Transactions/v2/Analytics/components/Shimmer';
import {
  BottomCardWrapper,
  ViewDetailsPrefix,
} from 'apps/self-serve/src/App/Transactions/v2/Analytics/styled';
import { BottomOverviewCardProps } from 'apps/self-serve/src/App/Transactions/v2/Analytics/types';
import {
  getLandingPageAnalyticsToolTip,
  cardLink,
} from 'apps/self-serve/src/App/Transactions/v2/Analytics/utils';
import { TooltipWrapper } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/styled';
import { track } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import CardFooter from './CardFooter';
import CardIcon from './CardIcon';

const BottomOverviewCard = ({
  currency,
  data,
  footerValues,
  durationOption,
}: BottomOverviewCardProps): JSX.Element | null => {
  const [isHover, setIsHover] = useState(false);
  const navigate = useNavigate();
  const { name, loading: isLoading, value, isAmount, failed: isFailed } = data;
  const orgName = window.rzp_org?.business_name;
  const goToEntityPage = () => {
    track({
      objectName: `${name} Tab`,
      properties: { overviewDate: durationOption.title, section: 'Overview' },
    });
    navigate(cardLink[name]);
    sessionStorage.setItem('overviewDuration', JSON.stringify(durationOption));
  };
  if (isLoading) {
    return <CardShimmer />;
  }
  return (
    <BottomCardWrapper onClick={goToEntityPage}>
      <Box onMouseEnter={() => setIsHover(true)} onMouseLeave={() => setIsHover(false)}>
        <Box flex="1">
          <Card
            backgroundColor={
              isHover ? 'surface.background.gray.intense' : 'surface.background.gray.moderate'
            }
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
                    <Text weight="semibold" size="medium" color="surface.text.gray.subtle">
                      {name}
                    </Text>
                    <TooltipWrapper
                      onClick={(e: { stopPropagation: () => void }) => {
                        e.stopPropagation();
                      }}
                    >
                      <Tooltip
                        content={getLandingPageAnalyticsToolTip(orgName)[name]}
                        placement="top"
                      >
                        <TooltipInteractiveWrapper>
                          <InfoIcon color="feedback.icon.neutral.intense" size="small" />
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
                      value={paiseToRupees(value)}
                      type="heading"
                      size="large"
                    />
                  ) : (
                    <Heading color="surface.text.gray.normal" marginLeft="spacing.2" size="medium">
                      {formatNumber(value)}
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

export default BottomOverviewCard;
