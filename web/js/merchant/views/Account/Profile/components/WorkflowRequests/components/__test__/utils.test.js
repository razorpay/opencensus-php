import '@testing-library/jest-dom/extend-expect';
import {
  isWorkflowInClarification,
  isVisible,
  hideWorkflowStatus,
  showWorkflowStatus,
} from 'merchant/views/Account/Profile/components/WorkflowRequests/utils';
import moment from 'moment';

describe('WorkflowRequests utils', () => {
  describe('isWorkflowInClarification function', () => {
    test('should return true when workflow needs clarification', () => {
      const statuses = ['open', 'approved'];
      expect(
        isWorkflowInClarification(
          {
            request_under_validation: false,
            workflow_status: 'open',
            needs_clarification: 'Please upload clear video of your cancelled_cheque.',
          },
          statuses,
        ),
      ).toBeTruthy();
    });

    test('should return false when workflow does not need clarification', () => {
      const statuses = ['open', 'approved'];
      expect(isWorkflowInClarification(undefined, statuses)).toBeFalsy();
      expect(
        isWorkflowInClarification(
          {
            request_under_validation: false,
            workflow_status: 'open',
          },
          statuses,
        ),
      ).toBeFalsy();
    });
  });

  describe('isVisible function', () => {
    test('should return true when workflow status should be visible', () => {
      expect(isVisible(false, '123')).toBeTruthy();
      window?.localStorage.setItem(
        'workflow_status',
        JSON.stringify({
          'bank_detail_update--123': {
            expireAt: moment().add(1, 'days'),
            isVisible: true,
          },
        }),
      );
      expect(isVisible(true, '123')).toBeTruthy();
      window?.localStorage.removeItem('workflow_status');
    });

    test('should return false when workflow status should not be visible', () => {
      expect(isVisible(true, '123')).toBeFalsy();
    });
  });

  describe('hideWorkflowStatus function', () => {
    test('should hide workflow status when it is present in the localStorage', () => {
      window?.localStorage.setItem(
        'workflow_status',
        JSON.stringify({
          'bank_detail_update--123': {
            expireAt: moment().add(1, 'days'),
            isVisible: true,
          },
        }),
      );
      hideWorkflowStatus('123');
      const { isVisible } = JSON.parse(window?.localStorage.getItem('workflow_status'))[
        'bank_detail_update--123'
      ];
      expect(isVisible).toBeFalsy();
      window?.localStorage.removeItem('workflow_status');
    });

    test('should not hide workflow status when it is not present in the localStorage', () => {
      expect(hideWorkflowStatus('123')).toBeFalsy();
    });
  });

  describe('showWorkflowStatus function', () => {
    test('should set workflow status expireAt & isVisible values in the localStorage', () => {
      showWorkflowStatus('123');
      const { isVisible, expireAt } = JSON.parse(window?.localStorage.getItem('workflow_status'))[
        'bank_detail_update--123'
      ];
      expect(expireAt).toBeTruthy();
      expect(isVisible).toBeTruthy();
      window?.localStorage.removeItem('workflow_status');
    });
  });
});
