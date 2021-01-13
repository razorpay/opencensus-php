import React from 'react';
import styled from 'styled-components';
import { useQuery } from 'react-query';
import View from '@razorpay/blade/src/atoms/View';
import Flex from '@razorpay/blade/src/atoms/Flex';
import Text from '@razorpay/blade/src/atoms/Text';
import Space from '@razorpay/blade/src/atoms/Space';
import { getColor } from '@razorpay/blade/src/_helpers/theme';
import { spacings } from '@razorpay/blade/src/tokens';
import { fetch } from 'v2/services/rest/rest-fetch';
import { ProgressBar } from 'v2/components/ProgressBar';
import { useSnackbar } from 'v2/components/SnackBar/SnackbarContext';
import Card from '../../../../components/Card';
import useActivation from '../hooks/useActivation';
import BusinessModelDetails from './BusinessModelDetails';
import CurrentActivationProgress from './CurrentActivationProgress';
import FormIcon from './Icons/FormIcon.svg';
import OnboardingCardShimmer from './OnboardingCardShimmer';

const fetchPayments = async () => {
  const data = await fetch<any>({ url: 'payments' });
  return data;
};
// rgba(224, 228, 249, 0.38);
const Separator = styled(View)`
  height: 1px;
  background-color: ${({ theme }) => getColor(theme, 'cloud.950')};
  margin: ${(props) =>
    props.$onboardingMilestone === null
      ? `${spacings.xlarge} 0 ${spacings.xxlarge}`
      : `${spacings.large} 0`};
`;

const HeadingContainer = styled(View)`
  width: 100%;
`;

const OnboardingCard: React.FC = () => {
  const snackbar = useSnackbar();
  const { status: activationQueryStatus, data: activationData } = useActivation();
  const { status: paymentsQueryStatus, data: paymentsData } = useQuery('payments', fetchPayments, {
    onError: (err: any) => snackbar.error(err.response.errors[0]),
  });

  if (activationQueryStatus === 'loading' || paymentsQueryStatus === 'loading') {
    return <OnboardingCardShimmer />;
  }

  if (activationQueryStatus === 'error' || paymentsQueryStatus === 'error') {
    return <div>Something went wrong</div>;
  }

  return (
    <View>
      <Card padding={[2]} margin={[2]}>
        <Flex flexDirection="row" justifyContent="space-between">
          <View>
            <HeadingContainer>
              <Space margin={[0, 0, 0.5, 0]}>
                <HeadingContainer>
                  <Text size="large" weight="bold">
                    Activate Your Account
                  </Text>
                </HeadingContainer>
              </Space>
              {!!activationData.onboarding_milestone ? (
                <>
                  <Space margin={[1, 2, 0, 0]}>
                    <Text size="small" color="positive.960" weight="bold">
                      {activationData.activation_progress}% done
                    </Text>
                  </Space>
                  <Space margin={[0, 2, 0, 0]}>
                    <View>
                      <ProgressBar
                        progressBarCompletedColor="primary.700"
                        progressBarBackgroundColor="primary.200"
                        percentDone={activationData.activation_progress}
                        height="6px"
                      />
                    </View>
                  </Space>
                </>
              ) : (
                <Space margin={[0, 2.5, 0, 0]}>
                  <Text size="xsmall" color="shade.950">
                    Provide following details to start your activation process.
                  </Text>
                </Space>
              )}
            </HeadingContainer>
            <img src={FormIcon} alt="fill_activation_form_icon" />
          </View>
        </Flex>

        <Separator $onboardingMilestone={activationData.onboarding_milestone} />

        <BusinessModelDetails />

        <CurrentActivationProgress data={activationData} payments={paymentsData} />
      </Card>
    </View>
  );
};

export default OnboardingCard;
