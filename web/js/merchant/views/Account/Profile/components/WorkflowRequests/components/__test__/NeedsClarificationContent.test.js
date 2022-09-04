import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import NeedsClarificationContent from '../NeedsClarificationContent';
import { render, screen } from 'test-utils';

describe('NeedsClarificationContent', () => {
  const needsClarificationMessage = 'Please upload clear video of your cancelled cheque.';
  const defaultProps = {
    needsClarificationMessage,
    isBankAccountUpdateWorkflow: true,
  };

  const App = (props) => {
    return <NeedsClarificationContent {...defaultProps} {...props} />;
  };

  test('should render needsClarificationMessage when isBankAccountUpdateWorkflow is true', () => {
    render(<App />);
    expect(screen.getByText('Submit details as per the instruction below:')).toBeInTheDocument();
    expect(screen.getByText(needsClarificationMessage)).toBeInTheDocument();
  });

  test('should render needsClarificationMessage when isBankAccountUpdateWorkflow is false', () => {
    render(<App isBankAccountUpdateWorkflow={false} />);
    expect(screen.getByText('Needs Clarification on:')).toBeInTheDocument();
    expect(screen.getByText(needsClarificationMessage)).toBeInTheDocument();
  });
});
