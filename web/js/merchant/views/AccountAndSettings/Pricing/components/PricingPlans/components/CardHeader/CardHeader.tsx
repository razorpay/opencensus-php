import { Badge, Text, Heading } from '@razorpay/blade/components';
import React from 'react';
import { connect } from 'react-redux';

import { ProgressBar } from 'common/components/ProgressBar';
import { Flex } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/PricingPlans.styles';
import {
  CardHeaderLeftItem,
  ProgressBarContainer,
  StyledCardHeader,
} from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/components/CardHeader/CardHeader.styles';
import { CardHeaderPropsT } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/components/CardHeader/CardHeader.types';
import {
  PRICING_PLAN_STATUS,
  STATUS_DATA,
} from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/data';

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
  const cancelledStatus = subscriptionPlanData?.payment_subscription?.status;

  const pricing =
    subscriptionPlanData?.frequency === 'monthly'
      ? `₹ ${monthlyPlanAmount}/Month`
      : `₹ ${yearlyPlanAmount}/Year`;

  const nextBillingDate = String(
    new Date((timeToChargeAt || timeSubscriptionEnds) * 1000).toLocaleString('en-IN', {
      day: '2-digit',
      month: '2-digit',
      year: '2-digit',
    }),
  );

  const timeLeft = (timeToChargeAt || timeSubscriptionEnds) * 1000 - currentTime;
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
              <Text weight="semibold" size="large">
                {String(subscriptionPlanData?.plan?.name)}
              </Text>
            </div>
            <div className="status">
              <Badge emphasis="intense" color={statusData.variant} size="small">
                {statusData.label}
              </Badge>
            </div>
            <div className="pricing">
              <Text weight="regular" size="large" color="surface.text.gray.muted">
                {String(pricing)}
              </Text>
            </div>
          </CardHeaderLeftItem>
          {statusData.label === 'IN PROGRESS' ? (
            <Text size="small" weight="regular" color="surface.text.gray.normal">
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
                <Text size="small" weight="regular" color="surface.text.gray.normal">
                  {noOfDaysLeftToCharge} Days left
                </Text>
                <Flex gap={2}>
                  <Text
                    size="small"
                    weight="regular"
                    color={
                      cancelledStatus === PRICING_PLAN_STATUS.cancelled
                        ? 'feedback.text.negative.intense'
                        : 'surface.text.gray.disabled'
                    }
                  >
                    {cancelledStatus === PRICING_PLAN_STATUS.cancelled
                      ? 'Expiring At'
                      : 'Next Billing Date:'}
                  </Text>
                  <Text size="small" weight="semibold" color="surface.text.gray.normal">
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
              <Heading weight="semibold" size="medium">
                {String(subscriptionPlanData?.plan?.name)}
              </Heading>
            </div>
            <div className="status">
              <Badge emphasis="intense" color={statusData.variant} size="medium">
                {statusData.label}
              </Badge>
            </div>
            <div className="pricing">
              <Text weight="regular" size="large" color="surface.text.gray.muted">
                {pricing}
              </Text>
            </div>
          </CardHeaderLeftItem>
          {statusData.label === STATUS_DATA.IN_PROGRESS.label ||
          statusData.label === STATUS_DATA.PAYMENT_PROCESSING.label ? (
            <Text weight="regular" size="large" color="surface.text.gray.muted">
              Pricing Plan updation in-progress
            </Text>
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
                <Text size="small" weight="regular" color="surface.text.gray.normal">
                  {noOfDaysLeftToCharge} Days left
                </Text>
              </Flex>
              <Flex gap={3}>
                <Text
                  weight="regular"
                  color={
                    cancelledStatus === PRICING_PLAN_STATUS.cancelled
                      ? 'feedback.text.negative.intense'
                      : 'surface.text.gray.disabled'
                  }
                  size="large"
                >
                  {cancelledStatus === PRICING_PLAN_STATUS.cancelled
                    ? 'Expiring At'
                    : 'Next Billing Date:'}
                </Text>
                <Text weight="semibold" size="large">
                  {nextBillingDate}
                </Text>
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
