import React from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import ReferAndEarn, { PropsT } from './ReferAndEarn';

export default {
  title: 'referral/ReferaAndEarn',
  component: ReferAndEarn,
} as Meta;

const Template: Story<PropsT> = (args) => <ReferAndEarn {...args} />;

export const Default = Template.bind({});
Default.args = {
  isOneReferralDone: false,
  isHomePage: true,
  width: '430px',
};

export const HomePageReferralDone = Template.bind({});
HomePageReferralDone.args = {
  isOneReferralDone: true,
  isHomePage: true,
};

export const ReferSection = Template.bind({});
ReferSection.args = {
  isOneReferralDone: false,
  isHomePage: false,
};
