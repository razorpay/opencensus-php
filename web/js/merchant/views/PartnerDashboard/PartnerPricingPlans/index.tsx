import React from 'react';
import { Box, Spinner, Text } from '@razorpay/blade/components';
import isEmpty from 'lodash/isEmpty';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import PartnerPricingHeader from 'assets/partner-dashboard/pricing-plans/pricing-plans-header.svg';
import { ShowNotificationType } from 'common/typings';
import { showNotification } from 'merchant_common/reducers/notifications';

import PricingRulesTable from './PricingRulesTable';
import { PAYMENT_METHODS_IN_ORDER } from './constants';
import { usePartnerDefaultPricingPlan } from './hooks/usePartnerDefaultPricingPlan';
import { useSearchParams } from 'react-router-dom';

type PartnerPricingPlansProps = {
  showNotification: ShowNotificationType;
};
const PartnerPricingPlans = ({ showNotification }: PartnerPricingPlansProps): JSX.Element => {
  const [searchParams] = useSearchParams();
  const partnerId = searchParams.get('partner_id');
  const { data, isFetching } = usePartnerDefaultPricingPlan(partnerId, showNotification);
  if (isFetching) {
    return (
      <Box minHeight="500px" display="flex" justifyContent="center" alignItems="center">
        <Spinner
          testID="partner-pricing-plans-spinner"
          accessibilityLabel="spinner"
          size="xlarge"
        />
      </Box>
    );
  }
  const groupedRules = {};
  data?.rules?.forEach((rule) => {
    const { payment_method } = rule;
    if (!groupedRules[payment_method]) groupedRules[payment_method] = [];
    groupedRules[payment_method].push(rule);
  });
  return (
    <Box>
      <img width="100%" src={PartnerPricingHeader} alt="Partner Pricing Plans" />
      <Box
        padding="spacing.5"
        margin="spacing.5"
        backgroundColor="surface.background.level2.lowContrast"
      >
        <Box minHeight="500px">
          {PAYMENT_METHODS_IN_ORDER.map((payment_method) => {
            if (!groupedRules[payment_method]) return null;
            return (
              <Box
                key={payment_method}
                backgroundColor="brand.gray.300.lowContrast"
                marginBottom="spacing.5"
              >
                <PricingRulesTable
                  paymentMethod={payment_method}
                  rules={groupedRules[payment_method]}
                />
              </Box>
            );
          })}
          {isEmpty(groupedRules) ? (
            <Text color="feedback.text.negative.lowContrast">
              No Pricing Plan found for partner ID: '{partnerId}'
            </Text>
          ) : null}
        </Box>
      </Box>
    </Box>
  );
};

export default connect(
  () => ({}),
  (dispatch) => bindActionCreators({ showNotification }, dispatch),
)(PartnerPricingPlans);
