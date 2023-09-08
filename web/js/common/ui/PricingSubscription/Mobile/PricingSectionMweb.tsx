import React, { useState, useMemo } from 'react';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';
import rTracking from 'react-tracking';
import { withRouter } from 'react-router';
import {
  Text,
  Button,
  Title,
  ArrowRightIcon,
  ChevronsDownIcon,
  ChevronsUpIcon,
  List,
  ListItem,
  CheckIcon,
  Box,
} from '@razorpay/blade/components';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import type { PlansType } from 'common/ui/PricingSubscription/PricingSubscriptionProps.type';
import type { PricingSectionMwebProps } from 'common/ui/PricingSubscription/Mobile/PricingMweb.type';
import {
  PricingPlansDetail,
  PricingPlanName,
  PricingPlanContainer,
  StylePlanIconMweb,
  StyleDescription,
  StylePrice,
  StyleViewMore,
} from 'common/ui/PricingSubscription/Mobile/PricingMwebStyle';
import Image from 'common/ui/Image';
import {
  StyleMonthlyPrice,
  StylePercentageColor,
  StyleStrikePrice,
} from 'common/ui/PricingSubscription/PricingStyled';

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
    <List variant="unordered" icon={CheckIcon}>
      {filteredList.map((featureId): JSX.Element => {
        return (
          <ListItem key={featureId}>
            {featureIdToFeatureCopyMap[featureId]}&nbsp;{plans[featureId][togglePlan]}
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
        <Title size="medium">{plans?.title}</Title>
      </PricingPlanName>
      <StyleDescription>
        <Text size="large">{plans?.description}</Text>
      </StyleDescription>
      <StylePrice>
        <Title size="medium">
          {togglePlan === TogglePlanValue.monthly
            ? `₹${plans?.monthlyPrice?.toLocaleString()}/Month`
            : `₹${plans?.annualPrice?.toLocaleString()}/Year`}
        </Title>
      </StylePrice>
      {togglePlan === TogglePlanValue.monthly ? (
        <StyleMonthlyPrice>
          <Text contrast="high" size="large" type="placeholder">
            ₹{Math.floor(plans?.annualPrice / 12).toLocaleString()}/Month with Annual Plan
          </Text>
        </StyleMonthlyPrice>
      ) : null}
      {togglePlan === TogglePlanValue.annual ? (
        <StyleStrikePrice isMobile>
          <Text contrast="high" size="large" type="placeholder" variant="body">
            ₹
            {getMonthlyDiscount(
              plans?.monthlyPrice,
              plans?.annualPrice,
            ).projectedPrice.toLocaleString()}
          </Text>
          <StylePercentageColor>
            <Text size="large">
              {getMonthlyDiscount(plans?.monthlyPrice, plans?.annualPrice).percentSavings}% Off
            </Text>
          </StylePercentageColor>
        </StyleStrikePrice>
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
      {isViewMore ? (
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
      ) : null}
      <StyleViewMore onClick={toggleViewMore}>
        <Text size="large">{isViewMore ? 'View Less' : 'View All Benefits'}</Text>
        {isViewMore ? (
          <ChevronsUpIcon color="feedback.icon.neutral.lowContrast" size="medium" />
        ) : (
          <ChevronsDownIcon color="feedback.icon.neutral.lowContrast" size="medium" />
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
