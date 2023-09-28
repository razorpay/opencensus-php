import SuccessModal from 'merchant_common/containers/ReportsAsync/GenerateReportPanel/AddEmail/SuccessModal';
import { render, screen, act, waitFor } from 'common/services/test/test-utils';
import userEvent from '@testing-library/user-event';
import { createMemoryHistory } from 'history';

describe('Success Modal', () => {
  const defaultProps = {
    heading: 'Test heading',
    note: 'Test note',
    successButtonText: 'Test success button text',
    successButtonLink: 'Test success button link',
    onSubmit: jest.fn(),
  };

  beforeEach(() => {
    defaultProps.onSubmit.mockClear();
  });

  const history = createMemoryHistory();
  history.push = jest.fn();

  const App = (props) => <SuccessModal {...defaultProps} {...props} />;

  test('should render Success Modal content', async () => {
    await act(async () => {
      render(<App />);
      expect(screen.getByText(defaultProps.successButtonText)).toBeInTheDocument();
      expect(screen.getByText(defaultProps.heading)).toBeInTheDocument();
      expect(screen.getByText(defaultProps.note)).toBeInTheDocument();

      await waitFor(() => {
        expect(screen.getByText(/Updating/i)).toBeInTheDocument();
      });

      await waitFor(() => {
        expect(screen.getByText(defaultProps.successButtonText)).toBeInTheDocument();
      });
    });
  });

  test("should show 'Go to dashboard' when successButtonText is empty ", async () => {
    render(<App successButtonText={undefined} />);

    await waitFor(() => {
      expect(screen.getByText('Go to Dashboard')).toBeInTheDocument();
    });
  });

  test('should call onSubmit on clicking success button', async () => {
    render(<App />, {
      history,
    });

    await waitFor(async () => {
      const successButton = screen.getByText(defaultProps.successButtonText);
      await userEvent.click(successButton);
      expect(defaultProps.onSubmit).toHaveBeenCalled();
      expect(history.push).toHaveBeenCalled();
    });
  });

  test('should only close modal on clicking success button if onSubmit and successButtonLink are not defined', async () => {
    render(<App onSubmit={undefined} successButtonLink={undefined} />);

    await waitFor(async () => {
      const successButton = screen.getByText(defaultProps.successButtonText);
      await userEvent.click(successButton);
      expect(defaultProps.onSubmit).not.toHaveBeenCalled();
      expect(history.push).not.toHaveBeenCalled();
    });
  });
});
