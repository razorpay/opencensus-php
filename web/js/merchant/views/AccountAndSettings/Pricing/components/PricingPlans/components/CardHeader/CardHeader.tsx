import React from 'react';
import { Text, Badge, Heading } from '@razorpay/blade/components';
import { connect } from 'react-redux';

import { ProgressBar } from 'common/components/ProgressBar';
import { Flex } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/PricingPlans.styles';
import {
  ProgressBarContainer,
  CardHeaderLeftItem,
  StyledCardHeader,
} from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/components/CardHeader/CardHeader.styles';
import { CardHeaderPropsT } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/components/CardHeader/CardHeader.types';
import { STATUS_DATA } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/data';

const CardHeader = ({
  isMobileResolution,
  subscriptionPlanData,
  statusData,
}: CardHeaderPropsT): JSX.Element | null => {
  const timeToChargeAt = Number(subscriptionPlanData?.next_charge_at);
  const timeSubscriptionStarted = Number(subscriptionPlanData?.current_start);
  const timeSubscriptionEnds = Number(subscriptionPlanData?.current_end);
  const monthlyPlanAmount = Number(subscriptionPlanData?.plan?.monthly_plan_amount) / 100;
  const yearlyPlanAmount = Number(subscriptionPlanData?.plan?.yearly_plan_amount) / 100;
  const currentTime = new Date().getTime();

  const pricing =
    subscriptionPlanData?.frequency === 'monthly'
      ? `₹ ${monthlyPlanAmount}/Month`
      : `₹ ${yearlyPlanAmount}/Year`;

  const nextBillingDate = String(
    new Date(timeToChargeAt * 1000).toLocaleString('en-IN', {
      day: '2-digit',
      month: '2-digit',
      year: '2-digit',
    }),
  );

  const timeLeft = timeToChargeAt * 1000 - currentTime;
  const noOfDaysLeftToCharge = Math.ceil(timeLeft / 1000 / 60 / 60 / 24);

  let percentDaysProgressed =
    100 -
    Math.min(
      Math.max(
        Math.round(
          ((currentTime - timeSubscriptionStarted * 1000) * 100) /
            ((timeSubscriptionEnds - timeSubscriptionStarted) * 1000),
        ),
        0,
      ),
      100,
    );

  /**
   * If the progress is more than 0, normalise its value so that the starting point becomes `progressIndicatorMinWidth`.
   * This is so that the progress indicator appears correctly.
   * When the progress indicator is less than 6 it appears in cylindrical shape.
   */
  if (percentDaysProgressed !== 0) {
    const progressIndicatorMinWidth = 6;
    const normalisedIncrement = (100 - progressIndicatorMinWidth) / 100;

    percentDaysProgressed = progressIndicatorMinWidth + percentDaysProgressed * normalisedIncrement;
    percentDaysProgressed = Number(percentDaysProgressed.toFixed(2));
  }

  return (
    <StyledCardHeader>
      {isMobileResolution ? (
        <>
          <CardHeaderLeftItem>
            <div className="icon">
              <img
                src={subscriptionPlanData?.plan?.details?.icon?.src}
                alt={subscriptionPlanData?.plan?.details?.icon?.alt}
                height={18}
                width={18}
              />
            </div>
            <div className="title">
              <Heading size="small" weight="bold">
                {String(subscriptionPlanData?.plan?.name)}
              </Heading>
            </div>
            <div className="status">
              <Badge contrast="high" variant={statusData.variant} size="small">
                {statusData.label}
              </Badge>
            </div>
            <div className="pricing">
              <Heading size="small" type="muted" weight="regular">
                {String(pricing)}
              </Heading>
            </div>
          </CardHeaderLeftItem>
          {statusData.label === 'IN PROGRESS' ? (
            <Text size="small" weight="regular" type="normal">
              Pricing Page will be activated in 24-48 hours
            </Text>
          ) : (
            <Flex gap={4} flexDirection="column" width="100%">
              {percentDaysProgressed >= 0 ? (
                <ProgressBarContainer data-testid="progressbarContainer">
                  <ProgressBar
                    percentDone={percentDaysProgressed}
                    height="8px"
                    progressBarCompletedColor="emerald.900"
                    progressBarBackgroundColor="grey.500"
                  />
                </ProgressBarContainer>
              ) : null}
              <Flex gap={3} justifyContent="space-between" width="100%">
                <Text size="small" weight="regular" type="normal">
                  {noOfDaysLeftToCharge} Days left
                </Text>
                <Flex gap={2}>
                  <Text size="small" weight="regular" type="normal">
                    Next Billing Date:
                  </Text>
                  <Text size="small" weight="bold" type="normal">
                    {nextBillingDate}
                  </Text>
                </Flex>
              </Flex>
            </Flex>
          )}
        </>
      ) : (
        <>
          <CardHeaderLeftItem>
            <div className="icon">
              <img
                src={subscriptionPlanData?.plan?.details?.icon?.src}
                alt={subscriptionPlanData?.plan?.details?.icon?.alt}
                height={18}
                width={18}
              />
            </div>
            <div className="title">
              <Heading size="large" weight="bold">
                {String(subscriptionPlanData?.plan?.name)}
              </Heading>
            </div>
            <div className="status">
              <Badge contrast="high" variant={statusData.variant} size="medium">
                {statusData.label}
              </Badge>
            </div>
            <div className="pricing">
              <Heading size="small" type="muted" weight="regular">
                {pricing}
              </Heading>
            </div>
          </CardHeaderLeftItem>
          {statusData.label === STATUS_DATA.IN_PROGRESS.label ||
          statusData.label === STATUS_DATA.PAYMENT_PROCESSING.label ? (
            <Heading size="small" weight="regular" type="subdued">
              Pricing Plan updation in-progress
            </Heading>
          ) : (
            <Flex gap={4} flexDirection="column" alignItems="flex-end">
              <Flex gap={3}>
                {percentDaysProgressed >= 0 ? (
                  <ProgressBarContainer data-testid="progressbarContainer">
                    <ProgressBar
                      percentDone={percentDaysProgressed}
                      height="12px"
                      progressBarCompletedColor="emerald.900"
                      progressBarBackgroundColor="grey.500"
                    />
                  </ProgressBarContainer>
                ) : null}
                <Text size="small" type="normal" weight="regular">
                  {noOfDaysLeftToCharge} Days left
                </Text>
              </Flex>
              <Flex gap={3}>
                <Heading size="small" weight="regular">
                  Next Billing Date:
                </Heading>
                <Heading size="small" weight="bold">
                  {nextBillingDate}
                </Heading>
              </Flex>
            </Flex>
          )}
        </>
      )}
    </StyledCardHeader>
  );
};

const mapStateToProps = (state) => {
  return {
    isMobileResolution: state.app.isMobileResolution,
  };
};

export default connect(mapStateToProps, null)(CardHeader);
