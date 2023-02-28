import { render, screen } from 'test-utils';
import InternationalStatusLabel from 'merchant/components/InternationalStatusLabel';
import { titleCase } from 'common/utils/rzp-utils';

jest.mock('common/ui/Popover', () => ({
  __esModule: true,
  default: ({ children }) => children,
  PopoverBody: ({ children }) => children,
}));

jest.mock('@razorpay/blade/components', () => ({
  ...jest.requireActual('@razorpay/blade/components'),
  Badge: (props) => {
    const { Badge } = jest.requireActual('@razorpay/blade/components');
    return (
      <>
        <span>Badge Variant: {props.variant}</span>
        <Badge {...props} />
      </>
    );
  },
}));

const renderApp = (props) => render(<InternationalStatusLabel {...props} />);

describe('InternationalStatusLabel', () => {
  describe('When IE Revamp is true', () => {
    test.each([
      ['negative', 'request_rejected', ''],
      ['negative', 'rejected', 'Please contact support for any queries'],
      ['neutral', 'disabled', ''],
      ['information', 'access_requested', ''],
      ['information', 'in_review', ''],
      ['information', 'under_review', 'Usually takes 3-5 working days to review your request'],
      ['positive', 'enabled', ''],
      ['positive', 'approved', ''],
      ['positive', 'activated', ''],
      ['information', 'requested', ''],
      ['notice', 'no_website_added', ''],
      ['notice', 'action_required', ''],
    ])(
      'should render badge of variant: %s when status is %s and descrption: %s',
      (variant, status, description) => {
        renderApp({ status, isIERevamp: true });
        expect(screen.getByText(`Badge Variant: ${variant}`)).toBeInTheDocument();
        if (description) {
          expect(screen.getByText(description)).toBeInTheDocument();
        }
      },
    );
  });

  describe('When IE Revamp is false', () => {
    test.each([
      ['label-danger-light', 'request_rejected', ''],
      ['label-danger-light', 'rejected', 'Please contact support for any queries'],
      ['label-muted', 'disabled', ''],
      ['label-primary-light', 'access_requested', ''],
      ['label-primary-light', 'in_review', ''],
      [
        'label-primary-light',
        'under_review',
        'Usually takes 3-5 working days to review your request',
      ],
      ['label-success-light', 'enabled', ''],
      ['label-success-light', 'approved', ''],
      ['label-success-light', 'activated', ''],
      ['label-warning', 'no_website_added', ''],
      ['label-primary-light', 'requested', ''],
      ['label-action-required', 'action_required', ''],
    ])(
      'should render badge of variant: %s when status is %s and descrption: %s',
      (className, status, description) => {
        renderApp({ status });
        expect(screen.getByText(titleCase(status))).toBeInTheDocument();
        const internationalStatusLabel = screen.getByTestId('international-status-label');
        expect(internationalStatusLabel).toHaveClass(className);
        if (description) {
          expect(screen.getByText(description)).toBeInTheDocument();
        }
      },
    );
  });
});
