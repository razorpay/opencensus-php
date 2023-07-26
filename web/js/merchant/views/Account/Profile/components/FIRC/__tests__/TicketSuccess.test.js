// testable
import TicketSuccess from 'merchant/views/Account/Profile/components/FIRC/TicketSuccess';
///- testable

// utils
import { render, screen, userEvent } from 'test-utils';
///- utils

describe('test TicketSuccess', () => {
  test('Render TicketSuccess component', async () => {
    // Test props
    const onClose = jest.fn();

    // Render the component
    render(<TicketSuccess onClose={onClose} />);

    // Assertions
    expect(screen.getByText('Purpose code update request has been sent!')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Your new purpose code will be reviewed by our banking partner before it is approved.',
      ),
    ).toBeInTheDocument();
    expect(
      screen.getByText(
        'Purpose code changes are reflected on the dashboard usually within 48 hours.',
      ),
    ).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Close' })).toBeInTheDocument();

    // Trigger onClose function
    await userEvent.click(screen.getByRole('button', { name: 'Close' }));
    expect(onClose).toHaveBeenCalled();
  });
});
