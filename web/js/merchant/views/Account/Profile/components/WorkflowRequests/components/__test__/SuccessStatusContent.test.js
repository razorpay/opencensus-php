import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import SuccessStatusContent from '../SuccessStatusContent';
import { render, screen, fireEvent } from 'test-utils';
import * as utils from '../../utils';

describe('SuccessStatusContent', () => {
  const content = 'Your bank account has been successfully updated.';
  const merchantId = '123';
  const defaultProps = {
    content,
    isBankAccountUpdateWorkflow: true,
    user: {
      id: merchantId,
    },
  };

  const App = (props) => {
    return <SuccessStatusContent {...defaultProps} {...props} />;
  };

  describe('When isBankAccountUpdateWorkflow is true', () => {
    test('should render success status content', () => {
      render(<App />);
      expect(screen.getByText(content)).toBeInTheDocument();
    });

    test('should call hideWorkflowStatus with merchantId', () => {
      const hideWorkflowStatusSpy = jest.spyOn(utils, 'hideWorkflowStatus');
      const { container } = render(<App />);
      const closeIcon = container.querySelector('.i-close');
      fireEvent.click(closeIcon);
      expect(hideWorkflowStatusSpy).toHaveBeenCalledWith(merchantId);
    });
  });

  test('should render success status content when isBankAccountUpdateWorkflow is false', () => {
    render(<App isBankAccountUpdateWorkflow={false} />);
    expect(screen.getByText(content)).toBeInTheDocument();
  });
});
