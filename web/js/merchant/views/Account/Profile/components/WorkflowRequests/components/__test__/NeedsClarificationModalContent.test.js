import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render } from 'test-utils';
import NeedsClarificationModalContent from '../NeedsClarificationModalContent';

describe('Bank account update status component', () => {
  const defaultProps = {
    workflowType: 'bank_detail_update',
    workflows: {
      bank_detail_update: {
        workflow_exists: true,
        workflow_status: 'open',
        needs_clarification: 'wefbewnjkf',
        permission: 'edit_merchant_bank_detail',
        request_under_validation: false,
        tags: ['awaiting-customer-response'],
        loading: false,
        error: null,
      },
    },
    isSubmitting: true,
    isUploadingDocument: false,
    isResponseValid: true,
    isClarificationSubmitted: true,
    onChange: jest.fn(),
    onSubmit: jest.fn(),
    removeFile: jest.fn(),
    handleFileUpload: jest.fn(),
    onBiggerFileSize: jest.fn(),
    isBankAccountUpdateWorkflow: true,
  };

  const App = ({ props = {} }) => {
    return <NeedsClarificationModalContent {...defaultProps} {...props} />;
  };

  test('should render BankAccountUpdateState when isSubmitting is true', () => {
    const { getByText } = render(
      <App
        props={{
          isSubmitting: true,
        }}
      />,
    );
    expect(getByText('Submitting your bank details')).toBeInTheDocument();
  });

  test('should render BankAccountUpdateStatus when isSubmitting is false', () => {
    const { getByText } = render(<App props={{ isSubmitting: false }} />);
    const ctaBtn = getByText('Okay, got it');
    expect(ctaBtn).toBeInTheDocument();
  });

  describe('should render input form if isSubmitting and isClarificationSubmitted is false', () => {
    const drivingProps = {
      isSubmitting: false,
      isClarificationSubmitted: false,
    };

    test('should have input box for adding clarification', () => {
      const { getByText } = render(
        <App
          props={{
            ...drivingProps,
            isBankAccountUpdateWorkflow: false,
          }}
        />,
      );
      const textAreaLabel = getByText('Add your reply below');
      expect(textAreaLabel).toBeInTheDocument();

      const clarificationMessage = getByText(
        defaultProps.workflows.bank_detail_update.needs_clarification,
      );
      expect(clarificationMessage).toBeInTheDocument();
    });

    test('should show Submit Clarification button when isBankAccountUpdateWorkflow is false', () => {
      const { getByRole } = render(
        <App
          props={{
            ...drivingProps,
            isBankAccountUpdateWorkflow: false,
          }}
        />,
      );
      const submitButton = getByRole('button', {
        name: 'Submit Clarification',
      });
      expect(submitButton).toBeInTheDocument();
    });

    test('should render notify-error when isResponseValid is false', () => {
      const { getByText } = render(
        <App
          props={{
            ...drivingProps,
            isResponseValid: false,
          }}
        />,
      );
      expect(getByText('Note')).toBeInTheDocument();
      expect(getByText('Minimum 50 characters required')).toBeInTheDocument();
    });

    test('should disable submit button if isSubmitDisabled is true', () => {
      const { getByRole } = render(
        <App
          props={{
            ...drivingProps,
            isResponseValid: false,
            isBankAccountUpdateWorkflow: false,
          }}
        />,
      );
      const submitButton = getByRole('button', {
        name: 'Submit Clarification',
      });
      expect(submitButton).toHaveAttribute('disabled');
    });
  });
});
