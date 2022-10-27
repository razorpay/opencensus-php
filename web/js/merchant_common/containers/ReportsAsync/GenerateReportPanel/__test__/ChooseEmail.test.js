import { fireEvent, render, screen } from 'test-utils';
import ChooseEmail from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/ChooseEmail';

describe('Choose Email', () => {
  const defaultProps = {
    closeModal: jest.fn(),
    selectedEmails: [],
    emails: ['test1@gmail.com', 'test2@gmail.com'],
    onChange: jest.fn(),
  };

  const App = (props = {}) => {
    return <ChooseEmail {...defaultProps} {...props} />;
  };

  test('Should render choose email', () => {
    render(<App />);
    expect(
      screen.getByText('Select email addresses from below to which you want to send the reports.'),
    ).toBeInTheDocument();
    expect(screen.getByText('test1@gmail.com')).toBeInTheDocument();
    expect(screen.getByText('test2@gmail.com')).toBeInTheDocument();
  });

  test('Should call closeModal function on clicking close', () => {
    render(<App />);
    fireEvent.click(screen.getByTestId('modal-header-close-btn'));
    expect(defaultProps.closeModal).toHaveBeenCalled();
  });

  test('Should call onChange function on selecting emails', () => {
    render(<App />);
    fireEvent.click(screen.getByLabelText(defaultProps.emails[0]));
    expect(defaultProps.onChange).toHaveBeenCalled();
  });
});
