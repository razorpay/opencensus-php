import React, { useState } from 'react';
import Button from '@razorpay/blade/src/atoms/Button';
import { Story } from '@storybook/react/types-6-0.d';
import spacing from '@razorpay/blade/src/tokens/spacings';
import ProgressBarContinuous, { ProgressBarPropsT } from './ProgressBar';

export default {
  title: 'ProgressBarContinuous',
  component: ProgressBarContinuous,
};

export const ProgressBar: React.FC<ProgressBarPropsT> = () => {
  const [progress, setProgress] = useState(10);
  const increaseByFive = () => {
    let newProgress = progress + 5;
    if (newProgress > 100) newProgress = 100;
    setProgress(newProgress);
  };
  const increaseByTen = () => {
    let newProgress = progress + 10;
    if (newProgress > 100) newProgress = 100;
    setProgress(newProgress);
  };
  return (
    <>
      <ProgressBarContinuous percentDone={progress} />
      <br />
      <br />
      <Button onClick={increaseByFive}>Increase By 5</Button>
      <br />
      <br />
      <Button onClick={increaseByTen}>Increase By 10</Button>
    </>
  );
};

const Template: Story<ProgressBarPropsT> = (args) => <ProgressBarContinuous {...args} />;

export const ProgressBarContinuousWithControls = Template.bind({});

ProgressBarContinuousWithControls.args = {
  percentDone: 70,
  height: spacing.xsmall,
};
