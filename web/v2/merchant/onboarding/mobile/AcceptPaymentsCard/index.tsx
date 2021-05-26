import React from 'react';
import styled from 'styled-components';
import { useQuery } from 'react-query';
import { fetch } from 'v2/services/rest/rest-fetch';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Text from '@razorpay/blade-old/src/atoms/Text';
import View from '@razorpay/blade-old/src/atoms/View';
import { useSnackbar } from 'v2/components/SnackBar/SnackbarContext';
import useActivation from '../hooks/useActivation';
import { isUnregisteredBusiness } from '../services/utils';
import AcceptPaymentsIcon from './Icons/AcceptPaymentsIcon.svg';
import * as Messages from './Constants';

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

const getCardContent = (activationData, isWebsiteInWorkflow, internationalWorkflowData) => {
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

  if (isUnregisteredBusiness(activationData.business_type)) {
    if (isAccepted) {
      return (
        <>
          <Title content={Messages.INTERNATIONAL_FLOW.unreg.account_activated.title} />
          <Description content={Messages.INTERNATIONAL_FLOW.unreg.account_activated.description} />
        </>
      );
    }
    return null;
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
      return (
        <>
          <Title content={Messages.INTERNATIONAL_FLOW.af_wl_iaf_gl.l1_submitted.title} />
          <Description
            content={Messages.INTERNATIONAL_FLOW.af_wl_iaf_gl.l1_submitted.description}
          />
        </>
      );
    } else if (activationData.international_activation_flow === 'whitelist') {
      if (!businessWebsite) {
        if (isWebsiteInWorkflow) {
          return (
            <>
              <Title content={Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.l1_submitted.no_website} />
              <Description
                content={Messages.INTERNATIONAL_FLOW.af_wl_iaf_wl.l1_submitted.no_website}
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
  const { status: activationQueryStatus, data: activationData } = useActivation();
  const { data: internationalWorkflowData } = useQuery(
    'internationalWorkflowStatus',
    fetchInternationalProductStatus,
    {
      retry: false,
      staleTime: Infinity,
      onError: (err: any) => snackbar.error(err.response.errors[0]),
    },
  );
  const { status: websiteWorkflowQueryStatus, data: isWebsiteInWorkflow } = useQuery(
    'websiteWorkflowStatus',
    fetchWebsiteWorkflowStatus,
    {
      retry: false,
      staleTime: Infinity,
      onError: (err: any) => snackbar.error(err.response.errors[0]),
    },
  );

  const isError = activationQueryStatus === 'error' || websiteWorkflowQueryStatus === 'error';
  if (isError) {
    return <div>Something Went Wrong</div>;
  }
  if (activationData) {
    const content = getCardContent(activationData, isWebsiteInWorkflow, internationalWorkflowData);
    if ((activationData.onboarding_milestone === 'L1' || activationData.submitted) && content) {
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
