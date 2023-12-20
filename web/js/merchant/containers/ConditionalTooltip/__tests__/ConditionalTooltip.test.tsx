import React from 'react';

import ConditionalTooltip from 'merchant/containers/ConditionalTooltip';
import { render, screen, fireEvent, delay } from 'test-utils';

const defaultProps = { title: 'tooltip title', content: 'tooltip content', showTooltip: false };
describe('ConditionalTooltip', () => {
  const renderApp = (props = {}) => {
    render(
      <ConditionalTooltip {...defaultProps} {...props}>
        <>dummy child</>
      </ConditionalTooltip>,
    );
  };
  test('Should show tooltip when showTooltip true', async () => {
    renderApp({ showTooltip: true });

    expect(screen.queryByText('tooltip title')).not.toBeInTheDocument();

    fireEvent.mouseEnter(screen.getByTestId('tooltip-interactive-wrapper'));
    await delay(500);

    expect(screen.getByText('tooltip title')).toBeInTheDocument();
  });
  test('Should not render tooltip when showTooltip false', () => {
    renderApp({ showTooltip: false });
    expect(screen.queryByTestId('tooltip-interactive-wrapper')).not.toBeInTheDocument();
    expect(screen.queryByText('tooltip title')).not.toBeInTheDocument();
  });
});
