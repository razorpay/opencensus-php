import React, { ReactText, useState } from 'react';
import { Story, Meta } from '@storybook/react/types-6-0.d';
import Sa, { StatelessAccordionPropsT } from './StatelessAccordian';
import Panel from './Panel';

export default {
  title: 'Accordian',
  component: Sa,
} as Meta;

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types

const Template: Story<StatelessAccordionPropsT> = (args) => {
  const [expanded, setExpanded] = useState<ReactText[]>(['P1']);
  return (
    <Sa {...args} expanded={expanded} onChange={(_key, exp) => setExpanded(exp)}>
      <Panel key="P1" title="P1">
        Hola this is random content 1
      </Panel>
      <Panel key="P2" title="P2">
        Hola this is random content 2
      </Panel>
      <Panel key="P3" title="P3">
        Hola this is random content 3
      </Panel>
    </Sa>
  );
};

export const StatelessAccordian = Template.bind({});

StatelessAccordian.args = {
  accordian: false,
};
