import { screen } from 'test-utils';

jest.mock(
  'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/InternationalHPBanner/HPBanner',
  () => ({ type }) => (
    <div>
      <title>HomePage Banner</title>
      <span>{type}</span>
    </div>
  ),
);

jest.mock('merchant/views/Account/Profile/components/WorkflowRequests/utils', () => ({
  ...jest.requireActual('merchant/views/Account/Profile/components/WorkflowRequests/utils'),
  isVisible: () => true,
}));

export const defaultWorkflowDetails = {
  workflow_status: 'open',
  needs_clarification: false,
  request_under_validation: true,
  tags: [],
};

export const testBanner = () => expect(screen.getByText('HomePage Banner')).toBeInTheDocument();

export const testKnowMore = () => {
  expect(screen.getByText('Know More')).toBeInTheDocument();
  const knowMoreLink = screen.getByRole('link', {
    name: 'Know More',
  });
  expect(knowMoreLink).toHaveAttribute('href', '/payment-methods/international-payments');
};
