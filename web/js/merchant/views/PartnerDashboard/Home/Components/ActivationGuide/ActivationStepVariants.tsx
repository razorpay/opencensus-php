import React from 'react';
import { History } from 'history';
import {
  ActivationStatesT,
  AddMerchantSource,
  FUXStatusStateT,
  PartnerTypeT,
  StepContentT,
} from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import ActivationStep from 'merchant/views/PartnerDashboard/Home/Components/ActivationGuide/ActivationStep';

interface StartStepT {
  fuxStatus: FUXStatusStateT;
  partnerName: string;
  handleReferClient: (source: AddMerchantSource, arg?: string) => void;
}
export const StartReferringStep = ({
  fuxStatus,
  partnerName,
  handleReferClient,
}: StartStepT): JSX.Element => {
  const isFirstReferralDone = fuxStatus.value?.first_submerchant_added || false;
  const isCurrentStep = isFirstReferralDone === false;
  const isNextStep = false;
  const isCompletedStep = isFirstReferralDone === true;
  const stepContent: StepContentT = {
    title: 'Add or Refer Merchants',
    subTitle:
      "Start referring merchants to Razorpay's products and enjoy a lifetime of benefits with partner program",
    ctaText: 'Add New Account',
    onClickCTA: () => handleReferClient('activation-guide'),
    stepName: 'start-referring',
  };
  if (isCompletedStep) {
    stepContent.title = `Good Job ${partnerName}, Keep Referring`;
    stepContent.subTitle =
      'Help your referrals get started on Razorpay and start earning commissions';
  }
  const stepProps = {
    isCurrentStep,
    isNextStep,
    isCompletedStep,
    stepContent,
  };
  return <ActivationStep {...stepProps} />;
};

interface ActivateAccountStepT {
  activation_status: ActivationStatesT;
  fuxStatus: FUXStatusStateT;
  history: History;
}
export const ActivateAccountStep = ({
  activation_status,
  fuxStatus,
  history,
}: ActivateAccountStepT): JSX.Element => {
  const isFirstReferralDone = fuxStatus.value?.first_submerchant_added || false;
  const isCurrentStep = isFirstReferralDone === true;
  const isNextStep = isFirstReferralDone === false;
  const isCompletedStep = activation_status === 'activated';

  const stepContent: StepContentT = {
    title: 'Activate Account',
    subTitle: 'Give us a few details and become eligible for commissions',
    ctaText: 'Submit KYC',
    onClickCTA: () => {
      history.push('/activation');
    },
    stepName: 'activate-account',
  };
  // Note : above default stepContent for instantly_activated

  if (activation_status === 'under_review') {
    stepContent.title = 'Account activation in process';
    stepContent.subTitle =
      'Hold tight, you are one step closer to earning commissions. Our team is verifying your details.';
    stepContent.ctaText = null;
  }
  if (activation_status === 'needs_clarification') {
    stepContent.title = 'Submit a few more details';
    stepContent.subTitle = `Our team requires additional information, please check your registered mail id`;
    stepContent.ctaText = 'Submit KYC';
    stepContent.onClickCTA = () => {
      history.push('/activation');
    };
  }
  if (activation_status === 'rejected') {
    stepContent.title = 'Account Activation failed';
    stepContent.subTitle =
      'You can still refer clients, but you would not receive  commissions automatically. Raise a request with Razorpay support to complete the KYC';
    stepContent.ctaText = 'Contact Support';
    stepContent.onClickCTA = () => {
      if (window?.rzpTicketSystem?.openModal) {
        window.rzpTicketSystem.openModal();
      }
    };
  }
  if (activation_status === 'activated' || activation_status === 'activated_mcc_pending') {
    stepContent.title = 'Account Activated';
    stepContent.subTitle =
      'Hurray, Your account is now activated. (You will earn commissions once your referred clients register with us and start transacting)';
    stepContent.ctaText = null;
  }
  const stepProps = {
    isCurrentStep,
    isNextStep,
    isCompletedStep,
    stepContent,
  };
  return <ActivationStep {...stepProps} />;
};

interface IntegratingAPIStep {
  activation_status: ActivationStatesT;
  fuxStatus: FUXStatusStateT;
  partnerType: PartnerTypeT;
}
export const IntegratingAPIStep = ({
  activation_status,
  fuxStatus,
  partnerType,
}: IntegratingAPIStep): JSX.Element | null => {
  if (partnerType === 'reseller') return null;

  const isFirstReferralDone = fuxStatus.value?.first_submerchant_added || false;
  const isAccountActivated = activation_status === 'activated';
  const isCurrentStep = isAccountActivated;
  const isNextStep = isFirstReferralDone;
  const isCompletedStep = fuxStatus.value?.api_integration === true;

  // Note : default stepContent of aggregator
  const RZP_API_DOC = 'https://razorpay.com/docs/partners/aggregators/api-integration/';
  const RZP_OAUTH_DOC = 'https://razorpay.com/docs/oauth/';
  const stepContent: StepContentT = {
    title: 'Integrate using APIs',
    subTitle: (
      <>
        Integrate with{' '}
        <a href={RZP_API_DOC} rel="noopener noreferrer" target="_blank">
          Razorpay API
        </a>{' '}
        to accept payments on behalf of your clients and earn commissions
      </>
    ),
    ctaText: null,
    toolTip: 'You will not earn commissions until you integrate the APIs',
    stepName: 'integrate-api',
  };
  if (isCompletedStep) {
    stepContent.title = 'API Integration Successful';
    stepContent.subTitle =
      'Hurray, You have successfully integrated the APIs. You will start earning commissions shortly.';
  }

  if (partnerType === 'pure_platform') {
    stepContent.title = `Integrate using OAuth`;
    stepContent.subTitle = (
      <>
        Integrate with{' '}
        <a href={RZP_OAUTH_DOC} rel="noopener noreferrer" target="_blank">
          Razorpay OAuth
        </a>{' '}
        to allow your customers to receive payments via Razorpay on your platform
      </>
    );
    stepContent.ctaText = null;

    if (isCompletedStep) {
      stepContent.title = 'Integration Successful';
      stepContent.subTitle = 'Successful';
    }
  }

  const stepProps = {
    isCurrentStep,
    isNextStep,
    isCompletedStep,
    stepContent,
  };
  return <ActivationStep {...stepProps} />;
};

interface CommissionStep {
  activation_status: ActivationStatesT;
  fuxStatus: FUXStatusStateT;
  partnerType: PartnerTypeT;
  history: History;
}
export const CommissionStep = ({
  activation_status,
  fuxStatus,
  partnerType,
  history,
}: CommissionStep): JSX.Element | null => {
  if (partnerType === 'pure_platform') return null;

  const isFirstReferralDone = fuxStatus.value?.first_submerchant_added === true;
  const isAccountActivated = activation_status === 'activated';
  const isAPIIntegrationDone = fuxStatus.value?.api_integration === true;
  const isFirstSubMerchAcceptPayments = fuxStatus.value?.first_submerchant_accept_payments === true;
  const isFirstEarningGen = fuxStatus.value?.first_earning_generated === true;
  const isFirstInvoiceGen = fuxStatus.value?.first_commission_payout === true;

  let isCurrentStep = false;
  let isNextStep = false;
  if (partnerType === 'reseller') {
    isCurrentStep = isAccountActivated;
    isNextStep = isFirstReferralDone;
  }
  if (partnerType === 'aggregator') {
    isCurrentStep = isAPIIntegrationDone;
    isNextStep = isAccountActivated;
  }
  const isCompletedStep = isFirstInvoiceGen;

  const registrationDoc = 'https://razorpay.com/docs/partners/new-merchant/';
  const stepContent: StepContentT = {
    title: 'Start Earning',
    subTitle: 'Ask your referrals to complete registration, and start earning commissions',
    ctaText: null,
    toolTip: (
      <>
        The referred sub-clients need to complete registration on Razorpay using following a few
        steps{' '}
        <a href={registrationDoc} target="_blank" rel="noopener noreferrer">
          Link
        </a>{' '}
        . Post this you will start earning commissions whenever your referrals transact using our
        platform.
      </>
    ),
    stepName: 'commission-step',
  };
  if (isFirstSubMerchAcceptPayments) {
    stepContent.subTitle = 'Start earning by getting your clients to start using our products';
    stepContent.ctaText = 'View Earnings';
    stepContent.onClickCTA = () => {
      history.push('/partners/earnings/daily');
    };
    stepContent.toolTip =
      'Invoices are generated only if the monthly commission is greater than 1 Rupee';
  }
  if (isFirstEarningGen) {
    stepContent.title = 'Get paid';
    stepContent.subTitle = 'Generate invoice to get earnings in your bank account';
    stepContent.ctaText = 'View Invoice';
    stepContent.onClickCTA = () => {
      history.push('/partners/earnings/invoices');
    };
  }

  const stepProps = {
    isCurrentStep,
    isNextStep,
    isCompletedStep,
    stepContent,
  };
  return <ActivationStep {...stepProps} />;
};
