// utils
import { render, screen, userEvent } from 'test-utils';

// testable
import SubmitForm from 'merchant/views/Settings/Configuration/Questionnaire/SubmitForm';

describe('Test <SubmitForm /> component', () => {
  test('should render without breaking', () => {
    render(<SubmitForm />);
    expect(screen.getByText('Submit Form')).toBeInTheDocument();
  });

  test('should toggle checkbox', async () => {
    render(<SubmitForm />);

    const checkbox = screen.getByRole('checkbox');
    await userEvent.click(checkbox);

    expect(checkbox).toBeChecked();
  });
});
