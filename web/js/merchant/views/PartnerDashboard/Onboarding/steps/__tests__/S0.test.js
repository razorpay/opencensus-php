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

  test('should render contents', () => {
    const props = {
      orgDetails: {
        custom_code: 'rzp',
      },
    };
    renderApp({ props });
    expect(screen.getByText('Welcome to your Partner Dashboard')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Get started with referring clients and track all your clients and do more, all directly from your partner dashboard.',
      ),
    ).toBeInTheDocument();
  });
});
