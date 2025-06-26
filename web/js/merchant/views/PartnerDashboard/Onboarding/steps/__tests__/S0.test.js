import S0 from 'merchant/views/PartnerDashboard/Onboarding/steps/S0';
import { render, screen } from 'test-utils';

jest.mock('merchant/views/PartnerDashboard/Onboarding/steps/SlideController', () => ({
  ...jest.requireActual('merchant/views/PartnerDashboard/Onboarding/steps/SlideController'),
  __esModule: true,
  default: () => {
    return <div>SlideController Component</div>;
  },
}));

// Mock analytics and tracking functions
const mockTrackEvent = jest.fn();
const mockTracking = {
  trackEvent: mockTrackEvent,
};

// Mock window objects
beforeAll(() => {
  window.rzpQ = {
    onbr: () => ({
      interaction: jest.fn(),
    }),
  };
  window.trackHubs = jest.fn();
  window.rzp_user = {};
});

const renderApp = ({ props } = {}) => {
  const defaultProps = {
    orgDetails: {
      custom_code: 'rzp',
    },
    tracking: mockTracking,
    merchantId: 'test_merchant_id',
    lpVariant: null,
    lpFold: null,
    businessTypeName: 'Registered',
    screenName: 'test_screen',
    sliderProps: {},
    ...props,
  };
  return render(<S0 {...defaultProps} />);
};

describe('test for S0 component', () => {
  beforeEach(() => {
    jest.clearAllMocks();
  });

  test('should return Curlec partner link on passing curlec as custom_code', () => {
    const props = {
      orgDetails: {
        custom_code: 'curlec',
      },
    };
    renderApp({ props });
    expect(screen.getByRole('link', { name: /Learn more about Partner Program/i })).toHaveAttribute(
      'href',
      'https://curlec.com/docs/partners/',
    );
  });

  test('should render Curlec specific content for curlec org', () => {
    const props = {
      orgDetails: {
        custom_code: 'curlec',
      },
    };
    renderApp({ props });
    expect(screen.getByText('Razorpay Partner Program')).toBeInTheDocument();
    expect(
      screen.getByText(
        "Refer Merchants to a complete suite of payment products. What's more, get rewarded for it!",
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        'From SaaS companies to Freelancers, our Partner Program is for anyone who can offer or advocate online payments.',
      ),
    ).toBeInTheDocument();
  });

  test('should render Razorpay specific content for rzp org', () => {
    const props = {
      orgDetails: {
        custom_code: 'rzp',
      },
    };
    renderApp({ props });
    expect(screen.getByText('Razorpay Partner Program')).toBeInTheDocument();
    expect(
      screen.getByText('Designed for digital enablers helping businesses go or grow online.'),
    ).toBeInTheDocument();
    expect(screen.getByText("Wondering if you're a fit?")).toBeInTheDocument();
    expect(
      screen.getByText('Do you offer web development, consulting, or SaaS/ERP solutions?'),
    ).toBeInTheDocument();
    expect(
      screen.getByText('Do your clients need reliable payment solutions?'),
    ).toBeInTheDocument();
  });

  test('should render partner logos for rzp org', () => {
    const props = {
      orgDetails: {
        custom_code: 'rzp',
      },
    };
    renderApp({ props });

    // Check for partner logos
    expect(screen.getByAltText('logo-dukaan')).toBeInTheDocument();
    expect(screen.getByAltText('logo-edumerge')).toBeInTheDocument();
    expect(screen.getByAltText('logo-djubo')).toBeInTheDocument();
    expect(screen.getByAltText('logo-shopaccino')).toBeInTheDocument();
  });

  test('should render SlideController component', () => {
    renderApp();
    expect(screen.getByText('SlideController Component')).toBeInTheDocument();
  });

  test('should use default razorpay URL for unknown org codes', () => {
    const props = {
      orgDetails: {
        custom_code: 'unknown_org',
      },
    };
    renderApp({ props });

    // Should render rzp content since it falls back to rzp for unknown org codes
    expect(
      screen.getByText('Designed for digital enablers helping businesses go or grow online.'),
    ).toBeInTheDocument();
  });
});
