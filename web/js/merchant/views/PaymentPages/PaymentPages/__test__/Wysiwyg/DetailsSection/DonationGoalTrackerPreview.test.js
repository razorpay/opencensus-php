import DonationGoalTrackerPreview from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/DetailsSection/DonationGoalTrackerPreview';
import moment from 'moment';
import { render, screen } from 'test-utils';

const defaultProps = {
  tracker_type: 'donation_amount_based',
  is_active: 1,
  meta_data: {
    goal_amount: '10000',
    collected_amount: '0',
    display_supporter_count: '1',
    supporter_count: '0',
    display_days_left: '1',
    goal_end_timestamp: '1657097182',
  },
  currency: 'MYR',
  countryCode: 'MY',
  endDate: moment(),
};

const renderApp = ({ props }) => render(<DonationGoalTrackerPreview {...props} />);

describe('test for DonationGoalTrackerPreview component', () => {
  test('should render goal in Indian format when country is India', () => {
    const props = { ...defaultProps, countryCode: 'IN', currency: 'INR' };
    props.meta_data.goal_amount = '1000000';
    renderApp({ props });
    expect(screen.queryByText('10,00,000', { exact: false })).toBeInTheDocument();
  });

  test('should render goal in Malaysian format when country is Malaysia', () => {
    const props = { ...defaultProps };
    props.meta_data.goal_amount = '1000000';
    renderApp({ props });
    expect(screen.queryByText('1,000,000', { exact: false })).toBeInTheDocument();
  });
});
