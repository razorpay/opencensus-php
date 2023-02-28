import { render, screen } from 'test-utils';
import { LeafListItemHeader } from 'merchant/views/AccountAndSettings/PaymentMethods/components/LeafListItem';
import React from 'react';

const defaultProps = {
  name: 'Cards',
  description: 'test description',
  actionComponent: <p>Action Component text</p>,
};

const renderApp = (props = {}) => render(<LeafListItemHeader {...defaultProps} {...props} />);

describe('LeafListItemHeader', () => {
  test('should render name, description and action component', () => {
    renderApp();
    expect(screen.getByText(defaultProps.name)).toBeInTheDocument();
    expect(screen.getByText(defaultProps.description)).toBeInTheDocument();
    expect(screen.getByText('Action Component text')).toBeInTheDocument();
  });

  test('should not render Action Component if its not defined', () => {
    renderApp({ actionComponent: undefined });
    expect(screen.queryByText('Action Component text')).not.toBeInTheDocument();
  });
});
