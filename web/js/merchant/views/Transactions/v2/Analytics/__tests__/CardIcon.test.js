import { render, screen } from 'test-utils';
import { PaymentTypes } from 'merchant/views/Transactions/v2/Analytics/types';
import CardIcon from 'merchant/views/Transactions/v2/Analytics/LandingAnalytics/BottomOverview/CardIcon';

describe('CardIcon Component', () => {
  test('renders Refunds icon for IN country users', () => {
    render(<CardIcon name={PaymentTypes.Refunds} />, {
      initialState: {
        session: {
          user: { isCountryIndia: true },
        },
      },
    });
    expect(screen.getByAltText('refund details')).toBeInTheDocument();
  });

  test('renders RotateCounterClockWiseIcon for non-IN country users', () => {
    render(<CardIcon name={PaymentTypes.Refunds} />, {
      initialState: {
        session: {
          user: { isCountryIndia: false },
        },
      },
    });
    expect(screen.getByTestId('RotateCounterClockWiseIcon')).toBeInTheDocument();
  });
});
