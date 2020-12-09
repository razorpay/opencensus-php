import React from 'react';
import Button from '@razorpay/blade/src/atoms/Button';
import Space from '@razorpay/blade/src/atoms/Space';
import View from '@razorpay/blade/src/atoms/View';
import { useActivationFormState } from '../context/store';
import { isUnregisteredBusiness } from '../Constants/OnboardingConstants';
import Info from './Info';
import * as Messages from './Constants';

const COUNT_TO_WORD = {
  1: 'one',
  2: 'two',
};

const FillDetailsCTA = () => (
  <Space margin={[2.5, 0, 0, 0]}>
    <View>
      <Button size="large" icon="arrowRight" iconAlign="right" block>
        Fill Remaining Details
      </Button>
    </View>
  </Space>
);

const EnablePaymentInfo = ({ data, incompleteEnablePaymentsSteps, totalIncompleteSteps }) => {
  let title = `You are just ${COUNT_TO_WORD[incompleteEnablePaymentsSteps]} ${
    incompleteEnablePaymentsSteps > 1 ? 'steps' : 'step'
  } away from enabling live payments`;
  let description = '';

  if (data.activation_flow === 'whitelist' && data.international_activation_flow === 'whitelist') {
    description = Messages.REMAINING_STEPS.af_wl.iaf_wl.enable_payments.multiple.description;
  } else if (
    data.activation_flow === 'whitelist' &&
    data.international_activation_flow === 'greylist'
  ) {
    if (incompleteEnablePaymentsSteps === 1) {
      description = Messages.REMAINING_STEPS.af_wl.iaf_gl.enable_payments.single.description;
    } else {
      description = Messages.REMAINING_STEPS.af_wl.iaf_gl.enable_payments.multiple.description;
    }
  } else if (data.activation_flow === 'greylist') {
    title = Messages.REMAINING_STEPS.af_gl.iaf_gl.multiple.title;
    description = Messages.REMAINING_STEPS.af_gl.iaf_gl.single.description;
    if (totalIncompleteSteps === 1) {
      title = Messages.REMAINING_STEPS.af_gl.iaf_gl.single.title;
    }
    if (data.international_activation_flow === 'blacklist') {
      description = Messages.REMAINING_STEPS.af_gl.iaf_bl.single.description;
    }
  } else {
    description = Messages.REMAINING_STEPS.unregistered.enable_payments.single.description;
  }

  return (
    <>
      <Info title={title} description={description} />
      <FillDetailsCTA />
    </>
  );
};

const EnableSettlementInfo = ({
  data,
  payments,
  incompleteSettlementEnableSteps,
  isBankAndCompanyDetailsCompleted,
}) => {
  let title = '';
  let description = '';

  if (isUnregisteredBusiness(data.business_type)) {
    title = `You are just ${COUNT_TO_WORD[incompleteSettlementEnableSteps]} ${
      incompleteSettlementEnableSteps > 1 ? 'steps' : 'step'
    } away from enabling settlements`;
    if (incompleteSettlementEnableSteps > 1) {
      description = Messages.REMAINING_STEPS.unregistered.enable_settlements.multiple.description;
    } else if (isBankAndCompanyDetailsCompleted) {
      description = Messages.REMAINING_STEPS.unregistered.enable_settlements.single.description;
    } else {
      description = 'Submit the bank details start recieving money in your bank account.'; // TODO: Use from constants and add test for this condition when Documents Upload screen is done
    }
  } else {
    title =
      Messages.REMAINING_STEPS.af_wl.iaf_wl.enable_settlements.live_transaction_not_done.multiple
        .title;
    if (payments && payments.items.length > 0) {
      title =
        Messages.REMAINING_STEPS.af_wl.iaf_wl.enable_settlements.live_transaction_done.multiple
          .title;
    }
    description =
      Messages.REMAINING_STEPS.af_wl.iaf_wl.enable_settlements.live_transaction_done.multiple
        .description;
    if (incompleteSettlementEnableSteps === 1) {
      if (payments && payments.items.length > 0) {
        title =
          Messages.REMAINING_STEPS.af_wl.iaf_wl.enable_settlements.live_transaction_done.single
            .title;
      }

      if (isBankAndCompanyDetailsCompleted) {
        description =
          Messages.REMAINING_STEPS.af_wl.iaf_wl.enable_settlements.live_transaction_done.single
            .description;
      } else {
        description = 'Submit the bank details and start recieving money in your bank account.'; // TODO: Use from constants and add test for this condition when Documents Upload screen is done
      }
    }
  }

  return (
    <>
      <Info title={title} description={description} />
      <FillDetailsCTA />
    </>
  );
};

const RemainingSteps = ({ data, payments }) => {
  const {
    isContactDetailsCompleted,
    isBusinessOverviewCompleted,
    isBusinessDetailsCompleted,
    isBankAndCompanyDetailsCompleted,
    isDocumentsUploadCompleted,
  } = useActivationFormState((state) => ({
    isContactDetailsCompleted: state.isContactDetailsCompleted,
    isBusinessOverviewCompleted: state.isBusinessOverviewCompleted,
    isBusinessDetailsCompleted: state.isBusinessDetailsCompleted,
    isBankAndCompanyDetailsCompleted: state.isBankAndCompanyDetailsCompleted,
    isDocumentsUploadCompleted: state.isDocumentsUploadCompleted,
  }));

  const allEnablePaymentsSteps = [
    isContactDetailsCompleted,
    isBusinessOverviewCompleted,
    isBusinessDetailsCompleted,
  ];
  const allSettlementEnableSteps = [isBankAndCompanyDetailsCompleted, isDocumentsUploadCompleted];
  const incompleteEnablePaymentsSteps =
    allEnablePaymentsSteps.length - allEnablePaymentsSteps.filter(Boolean).length;
  const incompleteSettlementEnableSteps =
    allSettlementEnableSteps.length - allSettlementEnableSteps.filter(Boolean).length;
  const totalIncompleteSteps = incompleteEnablePaymentsSteps + incompleteSettlementEnableSteps;

  const { onboarding_milestone } = data;

  if (onboarding_milestone !== 'l1_submitted') {
    return (
      <EnablePaymentInfo
        data={data}
        incompleteEnablePaymentsSteps={incompleteEnablePaymentsSteps}
        totalIncompleteSteps={totalIncompleteSteps}
      />
    );
  }

  if (incompleteSettlementEnableSteps) {
    return (
      <EnableSettlementInfo
        data={data}
        payments={payments}
        incompleteSettlementEnableSteps={incompleteSettlementEnableSteps}
        isBankAndCompanyDetailsCompleted={isBankAndCompanyDetailsCompleted}
      />
    );
  }

  return null;
};

export default RemainingSteps;
