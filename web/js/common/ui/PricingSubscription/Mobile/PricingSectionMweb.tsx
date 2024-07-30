import React, { useState, useMemo } from 'react';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';
import rTracking from 'react-tracking';
import { withRouter } from 'common/deprecated/withRouter';
import {
  Text,
  Button,
  Heading,
  ArrowRightIcon,
  ChevronsDownIcon,
  ChevronsUpIcon,
  List,
  ListItem,
  CheckIcon,
  Box,
  ListItemText,
} from '@razorpay/blade/components';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import type { PlansType } from 'common/ui/PricingSubscription/PricingSubscriptionProps.type';
import type { PricingSectionMwebProps } from 'common/ui/PricingSubscription/Mobile/PricingMweb.type';
import {
  PricingPlansDetail,
  PricingPlanName,
  PricingPlanContainer,
  StylePlanIconMweb,
  StyleViewMore,
  PriceContainer,
} from 'common/ui/PricingSubscription/Mobile/PricingMwebStyle';
import Image from 'common/ui/Image';
import { PercentageColor, StrikePrice } from 'common/ui/PricingSubscription/PricingStyled';

import {
  TogglePlanValue,
  getMonthlyDiscount,
  RedirectToastUI,
} from 'common/ui/PricingSubscription/PricingBundleCommon';
import { showNotification } from 'merchant_common/reducers/notifications';

const PlanBenefitList = ({
  plans,
  togglePlan,
  viewMore,
  featureIdToFeatureCopyMap,
}: {
  plans: PlansType;
  togglePlan: string;
  viewMore: boolean;
  featureIdToFeatureCopyMap: Record<string, string>;
}): JSX.Element => {
  const filteredList = useMemo(
    () =>
      Object.keys(featureIdToFeatureCopyMap).filter((featureId, index) => {
        if (viewMore) return featureId;
        else if (index < 2) return featureId;
        else return false;
      }),
    [viewMore],
  );

  return (
    <List
      size="large"
      variant="unordered"
      icon={() => <CheckIcon color="feedback.icon.positive.intense" size="medium" />}
    >
      {filteredList.map((featureId): JSX.Element => {
        return (
          <ListItem key={featureId}>
            <ListItemText color="surface.text.gray.subtle">
              {featureIdToFeatureCopyMap[featureId]}&nbsp;{plans?.[featureId]?.[togglePlan]}
            </ListItemText>
          </ListItem>
        );
      })}
    </List>
  );
};
const PricingSectionMweb = ({
  plans,
  toggleViewMore,
  isViewMore,
  togglePlan,
  featureIdToFeatureCopyMap,
  handleCheckoutPayment,
  closeBottomSheet,
  showNotificationToast,
  history,
  user,
  trackInstrumentation,
}: PricingSectionMwebProps): JSX.Element => {
  const [isLoading, setLoading] = useState(false);
  const [selectedPlanId, setSelectedPlanId] = useState('');

  const RedirectToast = (): JSX.Element => {
    const handleToastLink = () => {
      if (
        user.isAllowedMultiple(
          'webhooks applications configuration api_keys profile credits add_funds team referrals',
        ) &&
        user.isAccountAndSettingsRevampEnabled
      )
        history.push(ROUTES_INFO.PRICING_PLANS);
      else history.push(ROUTES_INFO.PRICING_PLANS_RELATIVE);
    };
    return <RedirectToastUI handleToastLink={handleToastLink} />;
  };

  const handlePaymentSuccess = (response, plans) => {
    closeBottomSheet(false);
    showNotificationToast({
      type: 'success',
      message: RedirectToast,
      closeTimeout: 15000,
    });
    trackInstrumentation('paymentSuccess', {
      value: 'success',
      payment_id: response?.razorpay_payment_id,
      toggle_switch: togglePlan,
      plan_id: plans.id,
      event_name: 'merchant_dashboard.subscription_checkout.success',
    });
  };
  const handlePaymentFailure = (response, plans) => {
    trackInstrumentation('', {
      value: 'failure',
      payment_id: response.error.metadata?.payment_id,
      response_code: response.error?.code,
      toggle_switch: togglePlan,
      plan_id: plans.id,
      event_name: 'merchant_dashboard.subscription_checkout.failure',
    });
  };

  const checkoutPayment = {
    trackInstrumentation,
    togglePlan,
    setLoading,
    setSelectedPlanId,
    handlePaymentSuccess,
    handlePaymentFailure,
    showNotificationToast,
  };

  return (
    <PricingPlanContainer>
      <PricingPlanName>
        <StylePlanIconMweb>
          <Image src={plans?.icon?.src} alt={plans?.icon?.alt} />
        </StylePlanIconMweb>
        <Heading size="large" weight="semibold" color="interactive.text.positive.normal">
          {plans?.title}
        </Heading>
      </PricingPlanName>
      {/* <Text size="large">{plans?.description}</Text> */}
      <PriceContainer>
        <Heading size="large" weight="semibold" color="interactive.text.positive.normal">
          {togglePlan === TogglePlanValue.monthly
            ? `₹${plans?.monthlyPrice?.toLocaleString()}/Month`
            : `₹${plans?.annualPrice?.toLocaleString()}/Year`}
        </Heading>
      </PriceContainer>
      {togglePlan === TogglePlanValue.monthly ? (
        <Box marginTop="spacing.2" marginBottom="spacing.7">
          <Text color="surface.text.gray.muted" size="large">
            ₹{Math.floor(plans?.annualPrice / 12).toLocaleString()}/Month with Annual Plan
          </Text>
        </Box>
      ) : null}

      {togglePlan === TogglePlanValue.annual ? (
        <StrikePrice isMobile>
          <Text color="surface.text.gray.muted" size="large">
            ₹
            {getMonthlyDiscount(
              plans?.monthlyPrice,
              plans?.annualPrice,
            ).projectedPrice.toLocaleString()}
          </Text>
          <PercentageColor>
            <Text size="large">
              {getMonthlyDiscount(plans?.monthlyPrice, plans?.annualPrice).percentSavings}% Off
            </Text>
          </PercentageColor>
        </StrikePrice>
      ) : null}
      <Box width="100%" marginX="spacing.0" marginY="spacing.7">
        <Button
          isLoading={isLoading && selectedPlanId === plans?.id}
          isDisabled={isLoading && selectedPlanId !== plans?.id}
          onClick={handleCheckoutPayment({ ...checkoutPayment, plans })}
          size="medium"
          isFullWidth
          type="button"
          variant={plans?.button?.variant}
          icon={ArrowRightIcon}
          iconPosition="right"
        >
          {plans?.button?.label}
        </Button>
      </Box>
      <PricingPlansDetail isViewMore>
        <PlanBenefitList
          viewMore={isViewMore}
          featureIdToFeatureCopyMap={featureIdToFeatureCopyMap}
          plans={plans}
          togglePlan={togglePlan}
        />
      </PricingPlansDetail>
      <StyleViewMore onClick={toggleViewMore}>
        <Text
          variant="body"
          size="large"
          color="interactive.text.neutral.normal"
          marginRight="spacing.2"
          weight="medium"
        >
          {isViewMore ? 'View Less' : 'View All Benefits'}
        </Text>
        {isViewMore ? (
          <ChevronsUpIcon color="interactive.icon.neutral.normal" size="medium" />
        ) : (
          <ChevronsDownIcon color="interactive.icon.neutral.normal" size="medium" />
        )}
      </StyleViewMore>
    </PricingPlanContainer>
  );
};
export default compose<any>(
  rTracking({
    page: 'DashboardBanner',
  }),
  withRouter,
  connect(
    (state) => ({
      user: state.session.user,
      ...state?.growthService?.gs_modals,
    }),
    (dispatch) => {
      return bindActionCreators(
        {
          showNotificationToast: showNotification,
        },
        dispatch,
      );
    },
  ),
)(PricingSectionMweb);
