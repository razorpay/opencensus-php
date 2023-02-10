import { LOADING_STATE } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/typings';
import { screen, userEvent } from 'test-utils';

jest.mock('@razorpay/blade/components', () => ({
  __esModule: true,
  ...jest.requireActual('@razorpay/blade/components'),
  Alert: ({ description, isFullWidth }) => (
    <>
      <div>{description}</div>
      {isFullWidth && <span>Full Width Alert</span>}
    </>
  ),
}));

export const TabData = [
  {
    id: LOADING_STATE.UPLOAD_CANCELLED_CHEQUE_DETAIL,
    title: 'Video of cancelled cheque (recommended)',
  },
  {
    id: LOADING_STATE.UPLOAD_VERIFICATION_LETTER_DETAIL,
    title: 'Bank verification letter',
  },
];

export const applyForm = async (withData) => {
  const file = new File(['hello'], 'hello.png', { type: 'image/png' });
  if (withData) {
    const fileInput = screen.getByTestId('file-uploader');
    await userEvent.upload(fileInput, file);
  }
  const submitAction = screen.getByRole('button', {
    name: 'Submit for verification',
  });
  await userEvent.click(submitAction);
};
