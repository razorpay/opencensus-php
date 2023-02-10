import { testBottomLinks, testFileUploadSection } from './mocks/fixtures/common';
import { applyForm, TabData } from './mocks/fixtures/UploadProofs';
import { bankAccountUpdateSuccess } from './mocks/handlers';
import * as fetchWorkflow from 'merchant/reducers/workflows';
import * as workflowUtils from 'merchant/views/Account/Profile/components/WorkflowRequests/utils';
import BankAccountUpdateFlow from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/BankAccountUpdateFlow';
import * as FormUtils from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/Form/utils';
import { FORM_DATA } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/steps/components/UploadProofs/FileUploadSection/data';
import { BANK_ACCOUNT_UPDATE_STEPS } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import * as ModalActions from 'merchant_common/reducers/modals';
import { render, screen, server, userEvent } from 'test-utils';

describe('UploadDocuments', () => {
  const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
  const workflowUtilsSpy = jest.spyOn(workflowUtils, 'showWorkflowStatus');
  const fetchWorkflowSpy = jest.spyOn(fetchWorkflow, 'fetchWorkflowStatus');
  const closeModalSpy = jest.spyOn(ModalActions, 'closeModal');

  const renderApp = ({ props, isMobile } = {}) => {
    return render(
      <BankAccountUpdateFlow defaultView={BANK_ACCOUNT_UPDATE_STEPS.UPLOAD_PROOF} {...props} />,
      {
        initialState: {
          app: {
            isMobileResolution: isMobile,
          },
        },
      },
    );
  };

  beforeEach(() => {
    showNotificationSpy.mockClear();
    workflowUtilsSpy.mockClear();
    fetchWorkflowSpy.mockClear();
    closeModalSpy.mockClear();
    jest
      .spyOn(FormUtils, 'getBankAccountBannerContent')
      .mockImplementation(
        () => 'The bank account must belong to the business PAN holder ******** only.',
      );
  });

  test('should render instruction and timeline', () => {
    renderApp();
    expect(
      screen.getByText(
        'As your bank account couldn’t be automatically verified, choose an additional proof from below for our team to verify in 2-3 days',
      ),
    ).toBeInTheDocument();
  });

  test.each([{ isMobile: true }, { isMobile: false }])(
    'should render alert with pan information based on device',
    ({ isMobile }) => {
      renderApp({
        isMobile,
      });
      expect(
        screen.getByText('The bank account must belong to the business PAN holder ******** only.'),
      ).toBeInTheDocument();
      if (isMobile) {
        expect(screen.queryByText('Full Width Alert')).not.toBeInTheDocument();
      } else {
        expect(screen.queryByText('Full Width Alert')).toBeInTheDocument();
      }
    },
  );

  testBottomLinks(renderApp, 'Submit for verification');

  describe('UploadSectionsTabs', () => {
    test.each(TabData)('should render tabs header items', ({ title }) => {
      renderApp();
      expect(screen.getByText(title)).toBeInTheDocument();
    });

    test('should render file upload field', () => {
      renderApp();
      expect(screen.getByText('File Upload Field')).toBeInTheDocument();
    });

    test.each(TabData)('should render tabs content based on active tab', async ({ id, title }) => {
      renderApp();
      const tabItem = screen.getByText(title);
      await userEvent.click(tabItem);
      const {
        description: { details, video },
      } = FORM_DATA[id];
      expect(screen.getByText(details.title)).toBeInTheDocument();
      details.items.forEach((item) => {
        expect(screen.getByText(item)).toBeInTheDocument();
      });
      if (video?.link) {
        const videoLink = screen.getByRole('link', {
          name: 'Watch sample video',
        });
        expect(videoLink).toHaveAttribute('href', video.link);
        expect(videoLink).toHaveAttribute('rel', 'noopener noreferrer');
      }
    });
  });

  testFileUploadSection(renderApp, () => {
    expect(showNotificationSpy).toHaveBeenCalledWith({
      type: 'error',
      message: `File exceeds total upload limit of 50MB!`,
    });
  });

  describe('UploadProofsSubmitActions', () => {
    test('should show error notification if proof are not available on submit action', async () => {
      renderApp();
      await applyForm();
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'error',
        message: `Please add any one document`,
      });
    });

    test('should call document upload api on submit action and trigger show workflow status', async () => {
      server.use(bankAccountUpdateSuccess());
      renderApp();
      await applyForm(true);
      expect(showNotificationSpy).toHaveBeenCalledWith({
        type: 'success',
        message: 'Proofs uploaded successfully',
      });
      expect(fetchWorkflowSpy).toHaveBeenCalledTimes(1);
    });

    test('should call document upload api on submit action and show error incase api fails', async () => {
      renderApp();
      await applyForm(true);
      expect(showNotificationSpy).toHaveBeenCalledWith(
        expect.objectContaining({
          type: 'error',
        }),
      );
    });
  });

  test('should close modal on Close CTA', async () => {
    renderApp();
    const submitCTA = screen.getByRole('button', {
      name: 'Cancel',
    });
    expect(submitCTA).toBeInTheDocument();
    await userEvent.click(submitCTA);
    expect(closeModalSpy).toHaveBeenCalled();
  });
});
