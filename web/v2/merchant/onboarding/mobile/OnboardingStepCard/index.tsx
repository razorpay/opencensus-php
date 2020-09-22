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
  isCTADisabled?: boolean;
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
  CTAText = 'Submit and Verify',
  onCTAClick = () => {},
}) => {
  const _subtitle = info || subtitle;
  return (
    <Card padding={[2]}>
      <Text size="medium" weight="bold" color="shade.970">
        {title}
      </Text>
      {_subtitle ? (
        <Text size="xsmall" color={info ? 'neutral.960' : 'shade.950'}>
          {subtitle}
        </Text>
      ) : null}
      <Space margin={[2.5, 0, 2.5, 0]}>
        <View>
          <StepList steps={steps} />
        </View>
      </Space>
      {showCTA ? (
        <Button onClick={onCTAClick} block>
          {CTAText}
        </Button>
      ) : null}
      {showCTA && !isCTADisabled ? (
        <Space margin={[1.5, 0, 0, 0]}>
          <Text size="xsmall" align="center">
            By submitting these details you agree to our{' '}
            <Link size="xsmall">terms and conditions</Link>
          </Text>
        </Space>
      ) : null}
      <Flex flexDirection="column">
        <Space margin={[2, 0, 0, 0]}>
          <View>
            <Button variant="tertiary" size="small" align="center">
              What are settlements
            </Button>
          </View>
        </Space>
      </Flex>
    </Card>
  );
};

export default OnboardingStepCard;
