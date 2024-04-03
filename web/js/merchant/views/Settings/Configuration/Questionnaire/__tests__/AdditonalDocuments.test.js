import * as Formik from 'formik';

import * as Ajax from 'merchant/utils/ajax';
import AdditionalDocuments from 'merchant/views/Settings/Configuration/Questionnaire/AdditionalDocuments';
import { initialStateForRevamp } from 'merchant/views/Settings/Configuration/Questionnaire/stateHelpers';
import { PRIVATE } from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';
import { render, screen, userEvent } from 'test-utils';

const useFormikContextSpy = jest.spyOn(Formik, 'useFormikContext');
const merchantFetchSpyOn = jest.spyOn(Ajax, 'merchantFetch');
const user = {
  business_type: '1',
};

const saveFormData = jest.fn();
const showNotification = jest.fn();

const createDummyFile = () => {
  const blob = new Blob(['upload text file']);
  const file = new File([blob], 'test.txt', {
    type: 'text/plain',
  });
  return file;
};

const renderComponent = (disabled = false) => {
  return render(
    <AdditionalDocuments
      disabled={disabled}
      saveFormData={saveFormData}
      showNotification={showNotification}
    />,
    {
      initialState: {
        session: {
          user,
        },
      },
    },
  );
};

describe('AdditionalDocuments', () => {
  beforeEach(() => {
    useFormikContextSpy.mockReturnValue({
      errors: {},
      values: initialStateForRevamp.initialValues,
      setFieldValue: jest.fn(),
    });
  });

  test('should render AdditionalDocuments component without breaking', () => {
    renderComponent();

    expect(screen.getAllByText(/Additional KYC document/)).toHaveLength(2);
  });

  test.skip('should handle document select change', async () => {
    renderComponent();

    await userEvent.selectOptions(screen.getAllByRole('combobox')[0], 'Udyam certificate');

    expect(screen.getByText(/An Udyam certificate is a/)).toBeInTheDocument();
    expect(screen.getByText(/Drop file here or/)).toBeInTheDocument();
  });

  test('should handle file upload', async () => {
    renderComponent();

    merchantFetchSpyOn.mockReturnValue(
      Promise.resolve({ data: { id: 'document1', display_name: 'Document.png' } }),
    );

    await userEvent.selectOptions(screen.getAllByRole('combobox').at(0), 'Udyam certificate');

    await userEvent.upload(screen.getByLabelText(/Drop file here or/), createDummyFile());

    expect(await screen.findByText(/Document.png/)).toBeInTheDocument();
    await userEvent.click(screen.getAllByTestId('btn-dropzone-close').at(0));

    expect(screen.getAllByText(/click to upload/)).toHaveLength(1);
  });

  test('should render with Private business type', async () => {
    user.business_type = PRIVATE;

    renderComponent();

    expect(screen.getByText(/Articles of Association/)).toBeInTheDocument();
    expect(screen.getByText(/Memorandom of Association/)).toBeInTheDocument();

    await userEvent.upload(screen.getAllByLabelText(/Drop file here or/).at(0), createDummyFile());

    expect(await screen.findByText(/Document.png/)).toBeInTheDocument();
    await userEvent.click(screen.getAllByTestId('btn-dropzone-close').at(0));

    expect(screen.getAllByText(/click to upload/)).toHaveLength(2);
  });
});
