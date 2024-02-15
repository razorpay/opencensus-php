import React from 'react';
import { Box, Spinner, Text } from '@razorpay/blade/components';
import isEmpty from 'lodash/isEmpty';
import { connect } from 'react-redux';
import { useSearchParams } from 'react-router-dom';
import { bindActionCreators } from 'redux';

import PartnerPricingHeader from 'assets/partner-dashboard/pricing-plans/pricing-plans-header.svg';
import { ShowNotificationType } from 'common/typings';
import { Org } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import { ORG_NAME } from 'merchant/views/PartnerDashboard/constants';
import { showNotification } from 'merchant_common/reducers/notifications';

import PricingRulesTable from './PricingRulesTable';
import { PAYMENT_METHODS_IN_ORDER } from './constants';
import { usePartnerDefaultPricingPlan } from './hooks/usePartnerDefaultPricingPlan';

type PartnerPricingPlansProps = {
  showNotification: ShowNotificationType;
  org: Org;
};
const PartnerPricingPlans = ({ showNotification, org }: PartnerPricingPlansProps): JSX.Element => {
  const orgName = org?.business_name || ORG_NAME.RZP;
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
          {!isEmpty(groupedRules) ? (
            <>
              <Text>
                We acknowledge and agree that the {orgName} Fees applicable for transactions
                initiated through OAuth shall be as provided hereinbelow:
              </Text>
              <br />
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
            </>
          ) : null}
          {isEmpty(groupedRules) ? (
            <Text color="feedback.text.negative.lowContrast">
              No Pricing Plan found for partner ID: &apos;{partnerId}&apos;
            </Text>
          ) : null}
        </Box>
      </Box>
    </Box>
  );
};

export default connect(
  (state) => ({ org: state.session.org }),
  (dispatch) => bindActionCreators({ showNotification }, dispatch),
)(PartnerPricingPlans);
