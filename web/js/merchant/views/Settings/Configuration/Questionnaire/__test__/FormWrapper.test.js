import { render, screen, waitFor } from 'test-utils';
import FormWrapper from 'merchant/views/Settings/Configuration/Questionnaire/FormWrapper';
import React from 'react';

const mockValidateForm = jest.fn();

jest.mock('formik', () => ({
  __esModule: true,
  ...jest.requireActual('formik'),
  useFormikContext: () => {
    return {
      errors: {
        products: 'cannot be empty',
      },
      values: {
        products: [],
      },
      validateForm: mockValidateForm,
    };
  },
}));

const defaultProps = {
  children: <span>children</span>,
  activeTab: 'active-tab',
  isRevampFlow: true,
  validateTab: jest.fn(),
};

const renderApp = (props = {}) => {
  return render(<FormWrapper {...defaultProps} {...props} />);
};

describe('FormWrapper', () => {
  test('should render children', () => {
    renderApp();
    expect(screen.getByText('children')).toBeInTheDocument();
  });

  describe('When isRevampFlow is enabled', () => {
    test('should render call validate form on mount', async () => {
      renderApp();
      await waitFor(() => {
        expect(mockValidateForm).toHaveBeenCalled();
      });
    });

    test('should call validateTab', async () => {
      renderApp();
      await waitFor(() => {
        expect(defaultProps.validateTab).toHaveBeenCalled();
        expect(defaultProps.validateTab).toHaveBeenCalledWith(
          {
            errors: {
              products: 'cannot be empty',
            },
            values: {
              products: [],
            },
          },
          false,
          defaultProps.activeTab,
        );
      });
    });
  });
});
