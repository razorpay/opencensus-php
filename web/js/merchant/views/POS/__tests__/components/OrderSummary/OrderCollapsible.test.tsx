import React from 'react';
import { render, screen, userEvent } from 'test-utils';

import OrderCollapsible from 'merchant/views/POS/OrderSummary/OrderCollapsible';

const initProps = {
  icon: <div>Icon Component</div>,
  title: <p>Some Test Title</p>,
  subTitle: 'Some Test SubTitle',
  children: <div> Order Summary</div>,
  headerWidgets: <button>Extra</button>,
  onCollapsibleChange: jest.fn(),
  defaultIsExpanded: false,
};

describe('<OrderCollapsible/>', () => {
  test('should render order collapsible component on screen', () => {
    render(<OrderCollapsible {...initProps} />);
    expect(screen.getByText('Some Test Title')).toBeVisible();
    expect(screen.getByText('Extra')).toBeVisible();
  });

  test('should render heading on screen both as JSX and text ', () => {
    const props = { ...initProps, title: 'Some Test Title' };
    render(<OrderCollapsible {...props} />);
    expect(screen.getByText('Some Test Title')).toBeVisible();
  });

  test('should collapse/expand on clicking on header', async () => {
    render(<OrderCollapsible {...initProps} />);
    expect(screen.getByText('Order Summary')).not.toBeVisible();
    await userEvent.click(screen.getByText('Some Test Title'));
    expect(screen.getByText('Order Summary')).toBeVisible();
  });

  test('should be expanded if default expanded is true', () => {
    const props = { ...initProps, defaultIsExpanded: true };
    render(<OrderCollapsible {...props} />);
    expect(screen.getByText('Order Summary')).toBeVisible();
  });
});
