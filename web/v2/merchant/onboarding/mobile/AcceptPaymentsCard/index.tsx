import React from 'react';
import styled from 'styled-components';
import axios from 'axios';
import { useQuery } from 'react-query';
import Space from '@razorpay/blade/src/atoms/Space';
import Text from '@razorpay/blade/src/atoms/Text';
import View from '@razorpay/blade/src/atoms/View';
import Button from '@razorpay/blade/src/atoms/Button';
import Flex from '@razorpay/blade/src/atoms/Flex';
import Link from '@commander/shield/src/shared/Link';
import useActivation from '../hooks/useActivation';
import AcceptPaymentsIcon from './Icons/AcceptPaymentsIcon.svg';

const ViewWithBackground = styled(View)`
  background: url("${AcceptPaymentsIcon}") right no-repeat;
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

const LinkButton = ({ content }) => (
  <Link size="xsmall" weight="bold" color="primary.800">
    {content}
  </Link>
);

const SecondaryButton = ({ content }) => (
  <Button variant="secondary" size="small">
    {content}
  </Button>
);

const getCardContent = (activationData, isWebsiteInWorkflow) => {
  const isAccepted = activationData.activation_status === 'activated';
  const businessWebsite = activationData.business_website;

  if (activationData.international_activation_flow === 'blacklist') {
    return (
      <>
        <Title content="Accept live payments!" />
        <Description
          content="You can start accepting domestic payments. International payments are currently not
        supported for your business model."
        />
        <LinkButton content="Know More" />
      </>
    );
  }

  if (activationData.activation_flow === 'whitelist') {
    if (activationData.international_activation_flow === 'greylist') {
      if (isAccepted) {
        return (
          <>
            <Title content="Accept International Payments!" />
            <Description content="You can now enable international payments." />
            <Flex alignItems="center">
              <View>
                <Space margin={[0, 1.5, 0, 0]}>
                  <View>
                    <SecondaryButton content="Enable" />
                  </View>
                </Space>
                <LinkButton content="Okay, Got it" />
              </View>
            </Flex>
          </>
        );
      }
      return (
        <>
          <Title content="Live Payments Enabled!" />
          <Description content="You can accept domestic payments. To enable international payments complete activation." />
          <LinkButton content="Okay, Got it" />
        </>
      );
    } else if (activationData.international_activation_flow === 'whitelist') {
      if (!businessWebsite) {
        if (isWebsiteInWorkflow) {
          return (
            <>
              <Title content="Accept Live Payments!" />
              <Description
                content="You can now start accepting domestic payments. Please raise a support ticket post
              website review to start accepting international payments."
              />
              <LinkButton content="Ok, Got it" />
            </>
          );
        }
        return (
          <>
            <Title content="Accept Live Payments!" />
            <Description content="You can accept domestic payments . To accept international payments update your website." />
            <Flex alignItems="center">
              <View>
                <Space margin={[0, 1.5, 0, 0]}>
                  <View>
                    <SecondaryButton content="Update Website" />
                  </View>
                </Space>
                <LinkButton content="Not Now" />
              </View>
            </Flex>
          </>
        );
      }
      if (isAccepted) {
        return (
          <>
            <Title content="Accept International Payments!" />
            <Description content="You can accept international payments via payment gateway and can enable inernational payments for other products too." />
            <Flex alignItems="center">
              <View>
                <Space margin={[0, 1.5, 0, 0]}>
                  <View>
                    <SecondaryButton content="Enable" />
                  </View>
                </Space>
                <LinkButton content="Not Now" />
              </View>
            </Flex>
          </>
        );
      }
      return (
        <>
          <Title content="Accept Live Payments!" />
          <Description content="You can accept domestic and international payments via payment gateway." />
          <LinkButton content="Okay, Got it" />
        </>
      );
    }
  }

  if (activationData.activation_flow === 'greylist') {
    return (
      <>
        <Title content="Accept live payments!" />
        <Description content="You can start accepting domestic payments and can enable international payments." />
        <Flex alignItems="center">
          <View>
            <Space margin={[0, 1.5, 0, 0]}>
              <View>
                <SecondaryButton content="Enable International Payments" />
              </View>
            </Space>
            <LinkButton content="Not Now" />
          </View>
        </Flex>
      </>
    );
  }

  return (
    <div>
      You can start accepting domestic payments. International payments may be restricted for your
      business model. Complete activation to know more.
    </div>
  );
};

const fetchInternationalProductStatus = () =>
  axios.get('/merchants/product_international/workflow/status/all').then((res) => res.data.data);

const AcceptPaymentsCard: React.FC = () => {
  const { status: activationQueryStatus, data: activationData } = useActivation();
  const { status: internationalWorkflowQueryStatus, data: internationalWorkflowData } = useQuery(
    'internationalWorkflowStatus',
    fetchInternationalProductStatus,
  );

  const isWebsiteInWorkflow = false;

  if (activationQueryStatus === 'loading' || internationalWorkflowQueryStatus === 'loading') {
    return <div>Loading</div>;
  }

  if (activationQueryStatus === 'error' || internationalWorkflowQueryStatus === 'error') {
    return <div>Something Went Wrong</div>;
  }

  console.log('workflowdata', internationalWorkflowData);

  const content = getCardContent(activationData, isWebsiteInWorkflow);

  return (
    <Space padding={[2, 6, 2, 2]}>
      <ViewWithBackground>{content}</ViewWithBackground>
    </Space>
  );
};

export default AcceptPaymentsCard;
