import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import CustomerRespondedContent from '../CustomerRespondedContent';
import { render, screen } from 'test-utils';

describe('CustomerRespondedContent', () => {
  const content = 'Your request is under review.';
  const defaultProps = {
    content,
    isBankAccountUpdateWorkflow: true,
  };

  const App = (props) => {
    return <CustomerRespondedContent {...defaultProps} {...props} />;
  };

  test('should render customer responded content when isBankAccountUpdateWorkflow is true', () => {
    render(<App />);
    expect(screen.getByText(content)).toBeInTheDocument();
  });

  test('should render customer responded content when isBankAccountUpdateWorkflow is false', () => {
    render(<App isBankAccountUpdateWorkflow={false} />);
    expect(screen.getByText(content)).toBeInTheDocument();
  });
});
