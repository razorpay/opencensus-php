import { IntegrationStep } from 'merchant/views/Optimizer/AddProvider/types';

export const INTEGRATION_TESTING_STEPS: IntegrationStep[] = [
  {
    title: 'Payment testing',
    value: 'payment_testing',
    active: true,
    success: false,
    failed: false,
  },
  {
    title: 'Refund testing',
    value: 'refund_testing',
    active: false,
    success: false,
    failed: false,
  },
  {
    title: 'Integration audit summary',
    value: 'integration_audit_summary',
    active: false,
    success: false,
    failed: false,
  },
  {
    title: 'Provider settings',
    value: 'provider_settings',
    active: false,
    success: false,
    failed: false,
  },
];
