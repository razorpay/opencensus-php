import React from 'react';
import { render } from 'test-utils';

import { RaiseTicketSuccess } from '../RaiseTicketSuccess';

describe('Optimizer RaiseTicketSuccess', () => {
  const mockProps = {
    isModalOpen: true,
    closeRaiseTicketSuccessModal: jest.fn(),
  };

  const renderApp = () => render(<RaiseTicketSuccess {...mockProps} />);

  it('should render the RaiseTicketSuccess component without any errors', () => {
    expect(() => renderApp()).not.toThrowError();
  });

  it('should render the RaiseTicketSuccess component with correct elements', () => {
    const { getByText, getByRole, queryAllByRole } = renderApp();
    expect(getByText('Optimizer Integration Testing')).toBeInTheDocument();
    expect(getByRole('img')).toBeInTheDocument();
    expect(getByRole('img')).toHaveAttribute('src', 'check_circle.svg');
    expect(getByText('Ticket raised successfully!')).toBeInTheDocument();
    expect(getByText('Our support team will reach out to you within 48 hours')).toBeInTheDocument();
    expect(queryAllByRole('button', { name: 'Close' })).toHaveLength(2);
  });
});
