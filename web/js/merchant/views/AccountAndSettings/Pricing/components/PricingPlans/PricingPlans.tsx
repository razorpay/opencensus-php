import React, { useEffect } from 'react';
import { compose, bindActionCreators } from 'redux';
import { withRouter } from 'common/deprecated/withRouter';
import { connect } from 'react-redux';
import {
  HeaderBodyDivider,
  StyledPricingPlans,
  LoaderContainer,
} from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/PricingPlans.styles';
import {
  PricingPlansProps,
  SubscriptionPlanDataT,
} from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/PricingPlans.types';
import { defaultErrorMessage } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/data';
import {
  CardBody,
  CardHeader,
} from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/components';
import { useQuery } from 'react-query';
import { fetch } from 'common/services/rest/rest-fetch';
import { Spinner, Text } from '@razorpay/blade/components';
import { getStatusData } from 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans/util';
import { useTracking } from 'react-tracking';
import { fetchEnrollmentStatus as fetchEnrollmentStatusFn } from 'merchant/reducers/bundlePricing';

const PricingPlans = ({
  enrollmentStatus,
  user,
  fetchEnrollmentStatus,
}: PricingPlansProps): JSX.Element | null => {
  const { trackEvent } = useTracking({ page: 'PricingPlans' });

  const { data, isLoading, status } = useQuery<SubscriptionPlanDataT | undefined>(
    `pricing-plans-${user?.current}`,
    async (): Promise<SubscriptionPlanDataT | undefined> => {
      const response = await fetch<any>({
        url: `pricing/merchant/subscriptions`,
      });

      if (response?.status_code && response.status_code !== 200) throw new Error();

      return response?.response?.subscription as SubscriptionPlanDataT | undefined;
    },
    {
      retry: false,
      staleTime: Infinity,
      enabled: !enrollmentStatus.loading && !enrollmentStatus.hasEnrolled === false,
    },
  );

  useEffect(() => {
    fetchEnrollmentStatus();
  }, [fetchEnrollmentStatus]);

  useEffect(() => {
    trackEvent(
      window.rzpQ &&
        window.rzpQ.merchantActions().success('merchant_dashboard.bank_details_impression'),
    );
  }, [trackEvent]);

  if (enrollmentStatus.loading || isLoading)
    return (
      <StyledPricingPlans>
        <LoaderContainer data-testid="LoaderContainer">
          <Spinner size="xlarge" accessibilityLabel="Loading..." />
        </LoaderContainer>
      </StyledPricingPlans>
    );

  if (enrollmentStatus?.hasEnrolled === false)
    return (
      <StyledPricingPlans>
        <Text>{enrollmentStatus.message || defaultErrorMessage}</Text>
      </StyledPricingPlans>
    );

  if (status === 'error')
    return (
      <StyledPricingPlans>
        <Text>{defaultErrorMessage}</Text>
      </StyledPricingPlans>
    );

  const statusData = getStatusData(data, data?.type);

  if (!statusData) {
    return (
      <StyledPricingPlans>
        <Text>{defaultErrorMessage}</Text>
      </StyledPricingPlans>
    );
  }

  return (
    <StyledPricingPlans>
      <CardHeader subscriptionPlanData={data} statusData={statusData} />
      <HeaderBodyDivider />
      <CardBody subscriptionPlanData={data} />
    </StyledPricingPlans>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  enrollmentStatus: state?.bundlePricing?.enrollmentStatus || {},
});

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      fetchEnrollmentStatus: fetchEnrollmentStatusFn,
    },
    dispatch,
  );
};

export default compose<any>(withRouter, connect(mapStateToProps, mapDispatchToProps))(PricingPlans);
