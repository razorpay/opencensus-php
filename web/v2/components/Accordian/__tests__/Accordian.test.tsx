import React, { useState, ReactText } from 'react';
import '@testing-library/jest-dom/extend-expect';
import StatelessAccordian from '../StatelessAccordian';
import Panel from '../Panel';
import { render, fireEvent, screen } from 'test-utils';

const Sa = () => {
  const [expanded, setExpanded] = useState<ReactText[]>(['P1']);
  return (
    <StatelessAccordian expanded={expanded} onChange={(_key, exp) => setExpanded(exp)}>
      <Panel key="P1" title="P1">
        content 1
      </Panel>
      <Panel key="P2" title="P2">
        content 2
      </Panel>
      <Panel key="P3" title="P3">
        content 3
      </Panel>
    </StatelessAccordian>
  );
};

test('Stateless accordian with 1 panel open', () => {
  render(<Sa />, {});
  expect(screen.getByText('content 1')).toBeInTheDocument();
  expect(screen.queryByText('content 2')).not.toBeInTheDocument();
  fireEvent.click(screen.getByText('P2'));
  expect(screen.getByText('content 2')).toBeInTheDocument();
});
