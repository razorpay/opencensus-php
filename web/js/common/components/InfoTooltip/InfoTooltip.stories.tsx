import React from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import styled from 'styled-components';

import View from '@razorpay/blade-old/src/atoms/View';

import InfoTooltip from './index';

export default {
  title: 'Info Tooltip',
  component: InfoTooltip,
} as Meta;

const Container = styled(View)`
  padding: 20px;
`;

const Template: Story<{}> = () => {
  const overlayText =
    'Mandatory for companies. PAN Details should be of the mentioned business only.';
  return (
    <Container>
      <InfoTooltip overlay={overlayText}>
        <input placeholder="Click to view info tooltip" />
      </InfoTooltip>
    </Container>
  );
};

export const DefaultInfoTooltip = Template.bind({});

DefaultInfoTooltip.args = {};
