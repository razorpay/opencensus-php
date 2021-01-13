import React from 'react';
import Space from '@razorpay/blade/src/atoms/Space';
import Text from '@razorpay/blade/src/atoms/Text';
import View from '@razorpay/blade/src/atoms/View';
import Flex from '@razorpay/blade/src/atoms/Flex';
import Link from '@commander/shield/src/shared/Link';
import Button from '@razorpay/blade/src/atoms/Button';
import Card from '../../../../components/Card';
import { StepList } from '../Step';
import { StepPropsT } from '../Step/Step';

interface OnboardingStepCardPropsT {
  steps: StepPropsT[];
  title: string;
  subtitle?: string;
  info?: string;
  showCTA?: boolean;
  showSettlement?: boolean;
  greyListFlowCanSubmit?: boolean;
  isCTADisabled?: boolean;
  activationFlow?: string;
  whiteListFlowCanSubmit?: boolean;
  CTAText?: '';
  onCTAClick?: () => void;
}

const OnboardingStepCard: React.FC<OnboardingStepCardPropsT> = ({
  title,
  subtitle,
  info,
  steps,
  showCTA = false,
  isCTADisabled = false,
  showSettlement = false,
  greyListFlowCanSubmit = false,
  whiteListFlowCanSubmit = false,
  CTAText = 'Submit and Verify',
  activationFlow = '',
  onCTAClick = () => {},
}) => {
  const _subtitle = info || subtitle;

  const canShowTermsAndCondition =
    activationFlow === 'greylist' ? greyListFlowCanSubmit : whiteListFlowCanSubmit;

  return (
    <Card padding={[2]}>
      <Text size="medium" weight="bold" color="shade.970">
        {title}
      </Text>
      {_subtitle ? (
        <Space margin={[0.5, 0, 0, 0]}>
          <Text size="xsmall" color={info ? 'neutral.960' : 'shade.950'}>
            {_subtitle}
          </Text>
        </Space>
      ) : null}
      <Space margin={[2.5, 0, 2.5, 0]}>
        <View>
          <StepList steps={steps} />
        </View>
      </Space>
      {showCTA ? (
        <Button
          size="large"
          disabled={
            activationFlow === 'greylist' ? !greyListFlowCanSubmit : !whiteListFlowCanSubmit
          }
          onClick={onCTAClick}
          block
        >
          {CTAText}
        </Button>
      ) : null}
      {canShowTermsAndCondition && showCTA && !isCTADisabled ? (
        <Space margin={[1.5, 0, 0, 0]}>
          <Text size="xsmall" align="center">
            By submitting these details you agree to our
            <Link href="https://razorpay.com/terms/" target="_blank" size="xsmall">
              terms and conditions
            </Link>
          </Text>
        </Space>
      ) : null}
      {showSettlement && (
        <Space margin={[2, 0, 0, 0]}>
          <Flex justifyContent="center">
            <View>
              <Link href="https://razorpay.com/docs/payment-gateway/settlements/" target="_blank">
                <Button variant="tertiary" size="small" align="center">
                  What are settlements
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
