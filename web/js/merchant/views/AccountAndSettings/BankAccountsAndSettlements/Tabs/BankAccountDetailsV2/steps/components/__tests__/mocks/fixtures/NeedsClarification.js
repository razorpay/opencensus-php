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
  await user.upload(screen.getByTestId('file-uploader'), file);
  await user.type(screen.getByRole('textbox'), response);
  const submitAction = screen.getByRole('button', {
    name: 'Submit details',
  });
  await user.click(submitAction);
};
