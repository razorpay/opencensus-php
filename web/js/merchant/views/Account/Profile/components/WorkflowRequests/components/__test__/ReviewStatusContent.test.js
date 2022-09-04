import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import ReviewStatusContent from '../ReviewStatusContent';
import { render, screen } from 'test-utils';

describe('ReviewStatusContent', () => {
  const content = 'Your request is under review.';
  const defaultProps = {
    content,
    isBankAccountUpdateWorkflow: true,
  };

  const App = (props) => {
    return <ReviewStatusContent {...defaultProps} {...props} />;
  };

  describe('When isBankAccountUpdateWorkflow is true', () => {
    test('should render review status content', () => {
      render(<App />);
      expect(screen.getByText(content)).toBeInTheDocument();
    });
  });

  test('should render review status content when isBankAccountUpdateWorkflow is false', () => {
    render(<App isBankAccountUpdateWorkflow={false} />);
    expect(screen.getByText(content)).toBeInTheDocument();
  });
});
