import { WORKFLOW_TYPES } from 'merchant/views/Account/Profile/components/WorkflowRequests/constants';
import { screen, userEvent } from 'test-utils';

export const workflowData = {
  workflow_exists: true,
  workflow_status: 'open',
  needs_clarification: 'upload clear photo of cancelled cheque',
  permission: 'edit_merchant_bank_detail',
  request_under_validation: false,
  tags: ['awaiting-customer-response'],
};

export const applyForm = async (response) => {
  const file = new File(['hello'], 'hello.png', { type: 'image/png' });
  const user = userEvent.setup();
  const textbox = await screen.findByRole('textbox');
  await user.type(textbox, response);
  await user.upload(screen.getByTestId('file-uploader'), file);
  const submitAction = screen.getByRole('button', {
    name: 'Submit details',
  });
  await user.click(submitAction);
};

export const testBasedOnWorkflowTypes = (testLabel, callback) => {
  test.each([
    WORKFLOW_TYPES.ENABLE_INTERNATIONAL_CARDS_FOR_PG_PPLI,
    WORKFLOW_TYPES.BANK_DETAIL_UPDATE,
  ])(`${testLabel} for %s`, async (workflowType) => {
    await callback(workflowType);
  });
};
