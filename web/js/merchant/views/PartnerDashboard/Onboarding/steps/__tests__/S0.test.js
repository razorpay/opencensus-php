import S0 from 'merchant/views/PartnerDashboard/Onboarding/steps/S0';
import { render, screen } from 'test-utils';

jest.mock('merchant/views/PartnerDashboard/Onboarding/steps/SlideController', () => ({
  ...jest.requireActual('merchant/views/PartnerDashboard/Onboarding/steps/SlideController'),
  __esModule: true,
  default: () => {
    return <div>SlideController Component</div>;
  },
}));

const renderApp = ({ props } = {}) => render(<S0 {...props} />);

describe('test for S0 component', () => {
  test('should return Curlec partner link on passing curlec as custom_code', () => {
    const props = {
      orgDetails: {
        custom_code: 'curlec',
      },
    };
    renderApp({ props });
    expect(screen.getByRole('link', { name: 'Learn more about Partner Program' })).toHaveAttribute(
      'href',
      'https://curlec.com/docs/partners/',
    );
  });

  test('should return Rzp partner link on passing rzp as custom_code', () => {
    const props = {
      orgDetails: {
        custom_code: 'rzp',
      },
    };
    renderApp({ props });
    expect(screen.getByRole('link', { name: 'Learn more about Partner Program' })).toHaveAttribute(
      'href',
      'https://razorpay.com/partners/',
    );
  });
});
