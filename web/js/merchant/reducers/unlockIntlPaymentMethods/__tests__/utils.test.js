import { getKycDocumentStatus } from 'merchant/reducers/unlockIntlPaymentMethods/utils';
import {
  ICProductStates,
  ProductWorkflowStatesInBackend,
} from 'merchant/views/AccountAndSettings/PaymentMethods/typings';

const testCases = [
  [
    ProductWorkflowStatesInBackend.NO_ACTION_RECEIVED,
    { needs_clarification: false, tags: [] },
    ICProductStates.NOT_ACTIVATED,
  ],
  [
    ProductWorkflowStatesInBackend.IN_REVIEW,
    { needs_clarification: true, tags: ['awaiting-customer-response'] },
    ICProductStates.ACTION_REQUIRED,
  ],
  [
    ProductWorkflowStatesInBackend.IN_REVIEW,
    { needs_clarification: false, tags: [] },
    ICProductStates.UNDER_REVIEW,
  ],
  [
    ProductWorkflowStatesInBackend.APPROVED,
    { needs_clarification: false, tags: [] },
    ICProductStates.ACTIVE,
  ],
  [
    ProductWorkflowStatesInBackend.REJECTED,
    { needs_clarification: false, tags: [] },
    ICProductStates.REJECTED,
  ],
  ['UNKNOWN_STATUS', { needs_clarification: false, tags: [] }, null],
];

describe('getKycDocumentStatus', () => {
  test.each(testCases)(
    'should return the expected status when workflowStatus is %s',
    (workflowStatus, workflowInfo, expected) => {
      const result = getKycDocumentStatus({
        workflowStatus,
        workflowInfo,
      });
      expect(result).toBe(expected);
    },
  );
});
