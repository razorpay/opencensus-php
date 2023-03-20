import React from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import styled from 'styled-components';
import Button from '@razorpay/blade-old/src/atoms/Button';
import Layer, { LayerPropsT } from './Layer';

export default {
  title: 'Layer',
  component: Layer,
  argTypes: {
    onDocClick: { action: 'Doc Clicked' },
    onEscape: { action: 'Escape Key' },
  },
} as Meta;

const Template: Story<LayerPropsT> = (args) => <Layer {...args} />;

export const BasicLayer = Template.bind({});
BasicLayer.args = {
  children: <div>Layer Child</div>,
};

const Wrapper = styled.div<any>`
  position: fixed;
  top: ${(props) => (props.$offset ? props.$offset : '10%')};
  left: ${(props) => (props.$offset ? props.$offset : '15%')};
  width: 200px;
  padding: 20px;
  background-color: ${(props) => props.color};
  text-align: center;
`;

const CompositeTemplate: Story<LayerPropsT> = (args) => {
  const [isFirstOpen, setIsFirstOpen] = React.useState(false);
  const [isSecondOpen, setIsSecondOpen] = React.useState(false);
  return (
    <React.Fragment>
      <Button onClick={() => setIsFirstOpen(true)}>Render Red Layer</Button>
      {isFirstOpen ? (
        <Layer {...args}>
          <Wrapper color="rgba(255, 190, 190, 0.86)">
            <Button onClick={() => setIsFirstOpen(false)}>Close</Button>
          </Wrapper>
        </Layer>
      ) : null}
      <br />
      <br />
      <Button onClick={() => setIsSecondOpen(true)}>Render Orange Layer</Button>
      {isSecondOpen ? (
        <Layer {...args}>
          <Wrapper color="rgba(255, 212, 135, 0.86)" $offset="15%">
            <Button onClick={() => setIsSecondOpen(false)}>Close</Button>
          </Wrapper>
        </Layer>
      ) : null}
    </React.Fragment>
  );
};

export const CompositeLayer = CompositeTemplate.bind({});
