import React from 'react';
import styled from 'styled-components';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Text from '@razorpay/blade-old/src/atoms/Text';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Link from '@razorpay/commander-shield/src/shared/Link';
import Button from '@razorpay/blade-old/src/atoms/Button';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import Card from 'common/components/Card';
import { StepList } from '../Step';
import { StepPropsT } from '../Step/Step';
import ErrorIcon from '../Step/Icons/error.svg';
import { useApp } from 'common/context/App';

interface OnboardingStepCardPropsT {
  steps: StepPropsT[];
  title: string;
  subtitle?: string;
  info?: string;
  errorInfo?: string;
  showCTA?: boolean;
  showSettlement?: boolean;
  canSubmitL2Form?: boolean;
  sucessInfo?: string;
  activationStatus?: string;
  goToNcFlow?: () => void;
  canSubmitL1Form?: boolean;
  CTAText?: string;
  onCTAClick?: () => void;
  milestone?: string;
}

const OnboardingStepCard: React.FC<OnboardingStepCardPropsT> = ({
  title,
  subtitle,
  info,
  errorInfo,
  steps,
  showCTA = false,
  activationStatus,
  showSettlement = false,
  canSubmitL2Form = false,
  canSubmitL1Form = false,
  CTAText = 'Submit KYC',
  goToNcFlow,
  sucessInfo,
  onCTAClick = () => {},
  milestone,
}) => {
  const _subtitle = info || errorInfo || sucessInfo;
  const { experiments } = useApp();

  const InfoScreen = styled(View)`
    background: ${({ color }) => color};
    border-radius: 4px;
  `;

  const ErrorImg = styled.img`
    height: 20px;
  `;
  const InfoText = styled(Text)`
    @media (max-width: 440px) {
      max-width: 240px;
    }
  `;
  const margin = _subtitle ? [1.5, 0, 0] : [0.5, 0, 0];

  return (
    <Card padding={[2]}>
      <Text size="medium" weight="bold" color="shade.970">
        {title}
      </Text>
      <Space margin={margin}>
        {_subtitle ? (
          <Space padding={[1.5]}>
            <InfoScreen color={info ? '#cd82141a' : errorInfo ? '#d12d2d1a' : '#1f890e1a'}>
              <Flex justifyContent="space-between">
                <View>
                  <InfoText
                    size="xsmall"
                    color={info ? 'neutral.900' : errorInfo ? 'negative.900' : 'positive.900'}
                    _lineHeight="medium"
                  >
                    {_subtitle}
                  </InfoText>

                  <Flex alignSelf="center">
                    <Space margin={[0, 2, 0, 0]}>
                      {info || sucessInfo ? (
                        <View>
                          <Icon
                            name={info ? 'clock' : 'paymentCapture'}
                            size="medium"
                            fill={info ? 'neutral.900' : 'positive.900'}
                          />
                        </View>
                      ) : (
                        <ErrorImg src={ErrorIcon} alt="error" />
                      )}
                    </Space>
                  </Flex>
                </View>
              </Flex>
              {activationStatus === 'needs_clarification' && (
                <Space margin={[1, 0, 0]}>
                  <View>
                    <Button
                      size="small"
                      onClick={goToNcFlow}
                      children="Clarify Details"
                      icon="chevronRight"
                      iconAlign="right"
                      block
                    />
                  </View>
                </Space>
              )}
            </InfoScreen>
          </Space>
        ) : (
          <Text size="xsmall" color="shade.950">
            {subtitle}
          </Text>
        )}
      </Space>
      <Space margin={[2.5, 0, 2.5, 0]}>
        <View>
          <StepList steps={steps} />
        </View>
      </Space>
      {showCTA ? (
        <Button
          size="large"
          disabled={
            !milestone
              ? canSubmitL1Form
              : milestone === 'L1' || !experiments.isInstantActivationEnabled
              ? canSubmitL2Form
              : true
          }
          onClick={onCTAClick}
          block
        >
          {CTAText}
        </Button>
      ) : null}
      {showSettlement && (
        <Space margin={[2, 0, 0, 0]}>
          <Flex justifyContent="center">
            <View>
              <Link
                href="https://razorpay.com/docs/payment-gateway/settlements/"
                target="_blank"
                rel="noreferrer noopener"
              >
                <Button
                  size="small"
                  variant="tertiary"
                  align="center"
                  icon="helpCircle"
                  iconAlign="right"
                >
                  What Are Settlements
                </Button>
              </Link>
            </View>
          </Flex>
        </Space>
      )}
    </Card>
  );
};

export default OnboardingStepCard;
