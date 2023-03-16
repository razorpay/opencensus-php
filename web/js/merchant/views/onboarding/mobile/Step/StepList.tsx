import React from 'react';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Step, { StepPropsT } from './Step';

export interface StepListPropsT {
  steps: StepPropsT[];
}

const StepList: React.FC<StepListPropsT> = ({ steps = [] }) => {
  const stepList = steps.map((step, index) => {
    if (index === steps.length - 1) {
      return <Step {...step} key={index} />;
    }

    return (
      <Space margin={[0, 0, 2, 0]} key={step.id}>
        <View>
          <Step {...step} />
        </View>
      </Space>
    );
  });

  return <>{stepList}</>;
};

export default StepList;
