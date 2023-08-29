import { render } from 'test-utils';

import BatchDetails from 'merchant/components/BatchNew/BatchDetails';

const batchDetails = {
  created_at: 1691482028,
  updated_at: 1691482030,
  id: 'batch_MNVCtOx54lra3C',
  entity_id: 'J1LL6RBx04m9It',
  name: 'TEST',
  batch_type_id: 'payment_page',
  mode: 'live',
  creator_id: 'J1LL6KNhnPcSGD',
  creator_type: 'user',
  is_scheduled: false,
  upload_count: 0,
  processed_count: 2,
  failure_count: 0,
  total_count: 2,
  success_count: 2,
  attempts: 0,
  status: 'processed',
  amount: 0,
  processed_amount: 0,
  schedule_time: null,
  type: 'payment_page',
  entity: 'batch',
  config: {
    draft: 0,
    version: '1.x',
    sms_notify: 1,
    email_notify: 1,
    payment_page_id: 'pl_MNVBA3Ms9NwZrc',
  },
};

const NO_RESULT_FOUND = 'No results found for given id';
const REPORT_LABEL = 'Download Batch Payment Page Report';
const BATCH_ID = 'batch_MNVCtOx54lra3C';
const BATCH_NAME = 'Test batch name with more than 24 char';

const renderApp = ({ props = {} } = {}) => {
  return render(<BatchDetails {...props} />);
};

export { batchDetails, NO_RESULT_FOUND, REPORT_LABEL, BATCH_ID, BATCH_NAME, renderApp };
