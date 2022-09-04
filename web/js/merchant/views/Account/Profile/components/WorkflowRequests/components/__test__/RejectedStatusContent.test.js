import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import RejectedStatusContent from '../RejectedStatusContent';
import { render, screen, fireEvent } from 'test-utils';
import * as utils from '../../utils';

describe('RejectedStatusContent', () => {
  const content = 'Your request is rejected.';
  const merchantId = '123';
  const defaultProps = {
    content,
    isBankAccountUpdateWorkflow: true,
    user: {
      id: merchantId,
    },
  };

  const App = (props) => {
    return <RejectedStatusContent {...defaultProps} {...props} />;
  };

  describe('When isBankAccountUpdateWorkflow is true', () => {
    test('should render rejected status content', () => {
      render(<App />);
      expect(
        screen.getByText(
          'Your bank account change request was rejected. Please check your email for details.',
        ),
      ).toBeInTheDocument();
    });

    test('should call hideWorkflowStatus with merchantId', () => {
      const hideWorkflowStatusSpy = jest.spyOn(utils, 'hideWorkflowStatus');
      const { container } = render(<App />);
      const closeIcon = container.querySelector('.i-close');
      fireEvent.click(closeIcon);
      expect(hideWorkflowStatusSpy).toHaveBeenCalledWith(merchantId);
    });
  });

  test('should render rejected status content when isBankAccountUpdateWorkflow is false', () => {
    render(<App isBankAccountUpdateWorkflow={false} />);
    expect(screen.getByText(content)).toBeInTheDocument();
  });
});
