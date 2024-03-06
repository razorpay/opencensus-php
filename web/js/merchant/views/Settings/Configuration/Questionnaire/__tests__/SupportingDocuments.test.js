import { Formik } from 'formik';
import { Provider } from 'react-redux';

import { storeWithInitialState } from 'merchant/store';
import SupportingDocuments from 'merchant/views/Settings/Configuration/Questionnaire/SupportingDocuments';
import {
  formInitialValues,
  getFormSchema,
} from 'merchant/views/Settings/Configuration/Questionnaire/utils';
import { render, screen, userEvent } from 'test-utils';

jest.mock('merchant/utils/ajax');

jest.mock('common/splitz', () => ({
  useSplitzService: jest.fn(() => ({
    abExperiments: {
      internationalAdditionalDocs: {
        variables: {
          result: 'on',
        },
      },
    },
  })),
}));

const onSubmit = jest.fn();

const renderComponent = (props, values) => {
  return render(
    <Provider
      store={storeWithInitialState({
        session: {
          mode: 'live',
          org: { id: '123' },
          user: {
            business_type: '4',
          },
        },
      })}
    >
      <Formik
        initialValues={{ ...formInitialValues, ...values }}
        enableReinitialize={true}
        validationSchema={getFormSchema()}
        onSubmit={onSubmit}
      >
        <SupportingDocuments {...props} />
      </Formik>
    </Provider>,
  );
};

let fileCounter = 0;
function createDummyFile() {
  const blob = new Blob([`upload text file ${fileCounter}`]);
  const file = new File([blob], `test-${fileCounter++}.txt`, {
    type: 'text/plain',
  });
  return file;
}

describe('Test <SupportingDocuments /> component', () => {
  test('Should render without breaking', () => {
    renderComponent();
    expect(screen.getByText('SUPPORTING DOCUMENTS')).toBeInTheDocument();
  });

  test('Should render all input/radio fields', async () => {
    const saveFormData = jest.fn();

    renderComponent(
      { saveFormData, disabled: true },
      {
        accepts_intl_txns: 'true',
      },
    );

    const yesRadioInput = screen.getByLabelText(/Yes/);
    await userEvent.click(yesRadioInput);
    await userEvent.click(screen.getByText('SUPPORTING DOCUMENTS')); // simulate blur

    // should not call as input is disabled
    expect(saveFormData).not.toHaveBeenCalled();

    const importExportCodeInput = screen.getByPlaceholderText('Enter I/E code here');
    await userEvent.type(importExportCodeInput, 'IE301');
    await userEvent.click(screen.getByText('SUPPORTING DOCUMENTS')); // simulate blur
    expect(saveFormData).not.toHaveBeenCalled();

    const fileUploaderInputs = screen.getAllByLabelText(/click to upload/);
    const dummyFile = createDummyFile();
    await userEvent.upload(fileUploaderInputs.at(0), dummyFile);

    expect(fileUploaderInputs.at(0).files).toHaveLength(0);
  });

  test('Should render all input/radio without disabled', async () => {
    const saveFormData = jest.fn();

    renderComponent(
      { saveFormData },
      {
        accepts_intl_txns: 'true',
        documents: {
          bank_statement_inward_remittance: 1,
          others: {
            1: [{ id: 'test1' }],
          },
        },
      },
    );

    await userEvent.click(screen.getByText('--Select-- (Optional)'));

    await userEvent.click(await screen.findByText('I/E Code'));

    const fileUploadInputs = screen.getAllByLabelText(/Drop file here or/);
    const allUploads = fileUploadInputs.map((input) => userEvent.upload(input, createDummyFile()));

    await Promise.all(allUploads);

    expect(screen.getAllByText((text) => text.includes('bytes'))).toHaveLength(
      fileUploadInputs.length,
    );
  });

  test('Should upload and remove the file', async () => {
    const saveFormData = jest.fn();

    renderComponent(
      { saveFormData },
      {
        documents: {
          bank_statement_inward_remittance: [{ id: 'bank_statement_inward_remittance' }],
          others: {
            1: [{ id: 'test1' }],
          },
        },
      },
    );

    await userEvent.click(screen.getAllByText(/Add another file/).at(0));

    const fileUploadInputs = screen.getAllByLabelText(/Drop file here or/);
    await userEvent.upload(fileUploadInputs.at(0), createDummyFile());

    expect(await screen.findByText(/bytes/)).toBeInTheDocument();
    await userEvent.click(screen.getAllByTestId('btn-file-remove').at(0));

    expect(screen.getAllByText(/click to upload/)).toHaveLength(1);
  });

  test('Should render non required fields', async () => {
    const saveFormData = jest.fn();

    renderComponent(
      { saveFormData },
      {
        documents: {
          bank_statement_inward_remittance: 1,
          others: {
            1: [{ id: 'test1' }],
          },
        },
      },
    );

    await userEvent.click(screen.getByText('--Select-- (Optional)'));
    await userEvent.click(screen.getByText('+Others, Specify'));

    const documentNameInput = screen.getByPlaceholderText('Please enter document name');
    await userEvent.type(documentNameInput, 'test 2');
    await userEvent.click(screen.getByText('+Add'));

    expect(await screen.findByText('test 2')).toBeInTheDocument();
  });

  test('should render additional documents if experiments are enabled', () => {
    renderComponent({
      isRevampFlow: true,
    });

    expect(screen.getByText(/Articles of Association/)).toBeInTheDocument();
    expect(screen.getByText(/Memorandom of Association/)).toBeInTheDocument();
  });
});
