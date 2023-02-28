import { screen, userEvent } from 'test-utils';
import { testBasedOnWorkflowTypes } from './NeedsClarification';

jest.mock('merchant/components/File/Upload', () => ({
  __esModule: true,
  default: ({
    onBiggerFileSize,
    onCloseClick,
    onFileChange,
    inputRef = {},
    onClickToUploadClick,
    onFileDrop,
  }) => (
    <>
      <div>File Upload Field</div>
      <input
        name="file-upload"
        data-testid="file-uploader"
        type="file"
        ref={(ref) => {
          inputRef = ref;
        }}
        onClick={onClickToUploadClick}
        onChange={(event) => {
          const file = event.currentTarget.files[0];
          if (file.size > 5) {
            onBiggerFileSize();
          } else {
            onFileChange({
              id: 'UPLOAD_CANCELLED_CHEQUE_DETAIL',
              type: 'ADD',
              file,
            });
          }
        }}
      />
      <button
        onClick={() => {
          onCloseClick({
            id: 'UPLOAD_CANCELLED_CHEQUE_DETAIL',
            type: 'REMOVE',
          });
          inputRef.value = '';
        }}
      >
        Remove File
      </button>
      <button onClick={onFileDrop}>File upload drop</button>
    </>
  ),
}));

jest.mock(
  'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/BottomActions',
  () => ({
    __esModule: true,
    default: ({ onClose, onSubmit, submitCTALabel }) => (
      <>
        <button onClick={onSubmit}>{submitCTALabel}</button>
        <button onClick={onClose}>Cancel</button>
      </>
    ),
  }),
);

export const testBottomLinks = (renderApp, submissionLabel) => {
  describe('BottomAction', () => {
    test('should render bottom actions', () => {
      renderApp();
      expect(screen.getByText(submissionLabel)).toBeInTheDocument();
      expect(screen.getByText('Cancel')).toBeInTheDocument();
    });
  });
};

export const testFileUploadSection = (renderApp, validateNotification, successHandler) => {
  describe('FileUploadSectionAction', () => {
    testBasedOnWorkflowTypes(
      'should render file upload input field and upload action',
      async (workflowType) => {
        successHandler && successHandler();
        const file = new File(['hello'], 'hello.png', { type: 'image/png' });
        renderApp({
          props: {
            workflowType,
          },
        });
        const fileInput = screen.getByTestId('file-uploader');
        expect(fileInput.files.length).toBe(0);
        await userEvent.upload(fileInput, file);
        expect(fileInput.files.length).toBe(1);
      },
    );

    testBasedOnWorkflowTypes('should remove file on close action click', async (workflowType) => {
      const file = new File(['hello'], 'hello.png', { type: 'image/png' });
      renderApp({
        props: {
          workflowType,
        },
      });
      const fileInput = screen.getByTestId('file-uploader');
      await userEvent.upload(fileInput, file);
      expect(fileInput.files.length).toBe(1);
      const removeAction = screen.getByRole('button', {
        name: 'Remove File',
      });
      await userEvent.click(removeAction);
      expect(fileInput.files.length).toBe(0);
    });

    testBasedOnWorkflowTypes(
      'should show error when file size breached the limit',
      async (workflowType) => {
        const file = new File(['hello razorpay'], 'hello.png', { type: 'image/png' });
        renderApp({
          props: {
            workflowType,
          },
        });
        const fileInput = screen.getByTestId('file-uploader');
        await userEvent.upload(fileInput, file);
        expect(fileInput.files.length).toBe(1);
        validateNotification();
      },
    );
  });
};
