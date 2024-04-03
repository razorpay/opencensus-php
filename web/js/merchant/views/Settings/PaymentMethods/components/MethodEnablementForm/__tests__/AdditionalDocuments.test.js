import * as Formik from 'formik';

import './mocks';

import AdditionalDocuments from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/components/AdditionalDocuments';
import { FORM_INITIAL_VALUES } from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/constants';
import { PRIVATE } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';
import { render, screen, userEvent } from 'test-utils';

const useFormikContextSpy = jest.spyOn(Formik, 'useFormikContext');
const formikDefaultReturnValues = {
  errors: {},
  values: FORM_INITIAL_VALUES,
  setFieldValue: jest.fn(),
};

const user = {
  business_type: '1',
};

const showNotification = jest.fn();

const renderComponent = () => {
  return render(<AdditionalDocuments showNotification={showNotification} />, {
    initialState: {
      session: {
        user,
      },
    },
  });
};

describe('Tests for AdditionalDocuments component - MethodEnablementForm', () => {
  beforeEach(() => {
    useFormikContextSpy.mockReturnValue(formikDefaultReturnValues);
  });

  test('Should render without breaking', () => {
    renderComponent();
    expect(screen.getByText('KYC Documents')).toBeInTheDocument();
  });

  test('Should show unchecked T&C by default', () => {
    renderComponent();

    const checkbox = screen.queryAllByRole('checkbox')[0];
    expect(checkbox).toBeInTheDocument();
    expect(checkbox.checked).toBe(false);
  });

  test.skip('should handle document select change', async () => {
    renderComponent();

    await userEvent.click(screen.getAllByRole('combobox')[0]);
    await userEvent.click(screen.getByRole('option', { name: 'Udyam certificate' }));

    await expect(screen.getAllByText(/Udyam certificate/)[0]).toBeInTheDocument();
    expect(screen.getByText(/Drop file here or/)).toBeInTheDocument();
  });

  test('should render with Private business type', () => {
    user.business_type = PRIVATE;

    renderComponent();

    expect(screen.getByText(/Articles of Association/)).toBeInTheDocument();
    expect(screen.getByText(/Memorandom of Association/)).toBeInTheDocument();
  });
});
