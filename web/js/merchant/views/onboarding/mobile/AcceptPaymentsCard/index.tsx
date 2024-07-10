import React from 'react';
import styled from 'styled-components';
import { useQuery } from '@tanstack/react-query';
import { fetch } from 'common/services/rest/rest-fetch';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Text from '@razorpay/blade-old/src/atoms/Text';
import View from '@razorpay/blade-old/src/atoms/View';
import { useSnackbar } from 'common/components/SnackBar/SnackbarContext';
import useActivation from 'merchant/views/onboarding/mobile/hooks/useActivation';
import {
  checkIfDedupe,
  isUnregisteredBusiness,
} from 'merchant/views/onboarding/mobile/services/utils';
import AcceptPaymentsIcon from './Icons/AcceptPaymentsIcon.svg';
import { useApp } from 'common/context/App';
import * as Messages from './Constants';
import { checkIfSignUpViaEasyOnboarding } from 'common/utils/activation';

const ViewWithBackground = styled(View)`
  background: url('${AcceptPaymentsIcon}') right no-repeat;
  box-shadow: 0px 4px 5px rgba(11, 112, 231, 0.05);
  background-color: #ffffff;
`;

const Title = ({ content }) => (
  <Space margin={[0, 0, 0.5, 0]}>
    <Text size="medium" weight="bold" color="shade.980">
      {content}
    </Text>
  </Space>
);

const Description = ({ content }) => (
  <Space margin={[0, 0, 1, 0]}>
    <Text size="small" color="shade.960">
      {content}
    </Text>
  </Space>
);

const getCardContent = ({ activationData, isWebsiteInWorkflow, internationalWorkflowData }) => {
  const isAccepted = activationData.activation_status === 'activated';
  const businessWebsite = activationData.business_website;
  const isAnyProductInReview =
    internationalWorkflowData &&
    (internationalWorkflowData.payment_gateway === 'in_review' ||
      internationalWorkflowData.payment_links === 'in_review');
  const isAnyProductRejected =
    internationalWorkflowData &&
    (internationalWorkflowData.payment_gateway === 'rejected' ||
      internationalWorkflowData.payment_links === 'rejected');
  const isPGIntlApproved =
    internationalWorkflowData && internationalWorkflowData.payment_gateway === 'approved';

  if (isAccepted) {
    if (isAnyProductInReview) {
      return (
        <>
          <Title content={Messages.INTERNATIONAL_REQUEST.in_review.title} />
          <Description content={Messages.INTERNATIONAL_REQUEST.in_review.description} />
        </>
      );
    }

    if (isAnyProductRejected) {
      return (
        <>
          <Title content={Messages.INTERNATIONAL_REQUEST.rejected.title} />
          <Description content={Messages.INTERNATIONAL_REQUEST.rejected.description} />
        </>
      );
    }
  }

  if (isUnregisteredBusiness(activationData.business_type) && isAccepted) {
    return (
      <>
        <Title content={Messages.INTERNATIONAL_FLOW.unreg.account_activated.title} />
        <Description content={Messages.INTERNATIONAL_FLOW.unreg.account_activated.description} />
      </>
    );
  }

  if (isAccepted && activationData.international_activation_flow === 'blacklist') {
    return (
      <>
        <Title content={Messages.INTERNATIONAL_BLACKLIST.title} />
        <Description content={Messages.INTERNATIONAL_BLACKLIST.description} />
      </>
    );
  }

  if (activationData.activation_flow === 'whitelist') {
    if (activationData.international_activation_flow === 'greylist') {
      if (isAccepted) {
        return (
          <>
            <Title content={Messages.INTERNATIONAL_FLOW.af_wl_iaf_gl.account_activated.title} />
            <Description
              content={Messages.INTERNATIONAL_FLOW.af_wl_iaf_gl.account_activated.description}
            />
          </>
        );
      }
    } else if (activationData.international_activation_flow === 'whitelist') {
      if (!businessWebsite) {
        if (isWebsiteInWorkflow) {
          return (
            <>
              <Title
                content={Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.l1_submitted.no_website.title}
              />
              <Description
                content={
                  Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.l1_submitted.no_website.description
                }
              />
            </>
          );
        }
        return (
          <>
            <Title content="Accept Live Payments!" />
            <Description content="You can accept domestic payments. To accept international payments update your website." />
          </>
        );
      }
      if (isAccepted && isPGIntlApproved) {
        return (
          <>
            <Title
              content={Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.account_activated.has_website.title}
            />
            <Description
              content={
                Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.account_activated.has_website.description
              }
            />
          </>
        );
      }

      if (isPGIntlApproved) {
        return (
          <>
            <Title
              content={Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.l1_submitted.has_website.title}
            />
            <Description
              content={
                Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.l1_submitted.has_website.description
              }
            />
          </>
        );
      }
      return (
        <>
          <Title content="Accept Live Payments!" />
          <Description content="You can accept domestic payments. Complete account activation to enable international payments." />
        </>
      );
    }
  }

  if (
    activationData.activation_flow === 'greylist' &&
    activationData.activation_status === 'activated'
  ) {
    return (
      <>
        <Title content={Messages.INTERNATIONAL_FLOW.af_gl_iaf_gl.title} />
        <Description content={Messages.INTERNATIONAL_FLOW.af_gl_iaf_gl.description} />
      </>
    );
  }

  return null;
};

const fetchInternationalProductStatus = () =>
  fetch<any>({ url: 'merchants/product_international/workflow/status/all', mode: 'live' }).then(
    (res) => {
      return res.data;
    },
  );

const fetchWebsiteWorkflowStatus = () =>
  fetch<any>({ url: 'merchant/activation/websites/status', mode: 'live' });

const AcceptPaymentsCard: React.FC = () => {
  const snackbar = useSnackbar();
  const { user, experiments } = useApp();
  const isInstantActivationEnabled = experiments.isInstantActivationEnabled;
  const isSignupWithEasyOnboarding = checkIfSignUpViaEasyOnboarding(user);

  const { status: activationQueryStatus, data: activationData } = useActivation();
  const { data: internationalWorkflowData } = useQuery({
    queryKey: ['internationalWorkflowStatus'],
    queryFn: fetchInternationalProductStatus,
    retry: false,
    staleTime: Infinity,
    onError: (err: any) => {
      if (err?.response?.errors) snackbar.error(err.response.errors[0]);
    },
  });
  const { status: websiteWorkflowQueryStatus, data: isWebsiteInWorkflow } = useQuery({
    queryKey: ['websiteWorkflowStatus'],
    queryFn: fetchWebsiteWorkflowStatus,
    retry: false,
    staleTime: Infinity,
    onError: (err: any) => {
      if (err?.response?.errors) snackbar.error(err.response.errors[0]);
    },
  });

  const isError = activationQueryStatus === 'error' || websiteWorkflowQueryStatus === 'error';

  if (isError) {
    return <div>Something Went Wrong</div>;
  }

  if (activationData) {
    const isDedupe =
      checkIfDedupe({ ...activationData, isInstantActivationEnabled }) === 'blocked' ||
      (activationData.activation_flow === 'blacklist' &&
        isSignupWithEasyOnboarding &&
        activationData.submitted);

    const content = getCardContent({
      activationData,
      isWebsiteInWorkflow,
      internationalWorkflowData,
    });

    if (
      (activationData.activation_form_milestone === 'L1' || activationData.submitted) &&
      content &&
      !isDedupe
    ) {
      return (
        <Space padding={[2, 6, 2, 2]}>
          <ViewWithBackground>{content}</ViewWithBackground>
        </Space>
      );
    }
  }

  return null;
};

export default AcceptPaymentsCard;
