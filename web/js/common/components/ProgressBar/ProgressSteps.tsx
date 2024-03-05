import React from 'react';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Text from '@razorpay/blade-old/src/atoms/Text';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Icon from '@razorpay/blade-old/src/atoms/Icon';

export interface ProgressStepsPropsT {
  headerText?: string;
  currentStep: number;
  totalSteps: number;
  icon?: string;
}

const ProgressBar: React.FC<ProgressStepsPropsT> = ({
  headerText,
  currentStep,
  totalSteps,
  icon = 'check',
}) => {
  const renderSteps = () => {
    const stepsArray: React.ReactNode[] = [];

    for (let i = 1; i <= totalSteps; i++) {
      stepsArray.push(
        <Icon name={icon} key={i} fill={i <= currentStep ? 'green.900' : 'grey.500'} />,
      );
    }
    return stepsArray;
  };

  return (
    <View>
      {headerText ? (
        <Flex>
          <Space margin={[0, 0, 1, 0.25]}>
            <Text color="shade.970" size="medium" _lineHeight="medium" weight="bold">
              {headerText}
            </Text>
          </Space>
        </Flex>
      ) : null}

      <Flex>
        <View data-testid="progressSteps">{renderSteps()}</View>
      </Flex>
    </View>
  );
};

export default ProgressBar;
