import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import AwaitingCustomerResponseContent from '../AwaitingCustomerResponseContent';
import { render, screen } from 'test-utils';

describe('AwaitingCustomerResponseContent', () => {
  const defaultProps = {
    isBankAccountUpdateWorkflow: true,
    needsClarificationMessage: 'Please upload clear video of your cancelled cheque.',
    showAddReplyButton: false,
    onReplyClick: jest.fn(),
  };

  const App = (props) => {
    return <AwaitingCustomerResponseContent {...defaultProps} {...props} />;
  };

  describe('When isBankAccountUpdateWorkflow is true', () => {
    test('should render awaiting customer response content', () => {
      render(<App />);
      expect(screen.getByText('Action required')).toBeInTheDocument();
      expect(
        screen.getByText(
          'We need a few more details to change your bank account - Please submit the required details -',
        ),
      ).toBeInTheDocument();
      expect(screen.getByText('Submit details')).toBeInTheDocument();
    });
  });

  describe('When isBankAccountUpdateWorkflow is false', () => {
    test('should render awaiting customer response content', () => {
      render(<App isBankAccountUpdateWorkflow={false} />);
      expect(
        screen.getByText('Please upload clear video of your cancelled cheque.'),
      ).toBeInTheDocument();
    });

    test('should show reply button when showAddReplyButton is true', () => {
      render(<App isBankAccountUpdateWorkflow={false} showAddReplyButton />);
      expect(screen.getByText('Add Reply')).toBeInTheDocument();
    });

    test('should not show reply button when showAddReplyButton is false', () => {
      render(<App isBankAccountUpdateWorkflow={false} showAddReplyButton={false} />);
      expect(screen.queryByText('Add Reply')).not.toBeInTheDocument();
    });
  });
});
