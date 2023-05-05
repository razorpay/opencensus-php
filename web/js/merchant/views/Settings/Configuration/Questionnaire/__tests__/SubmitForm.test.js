// utils
import { render, screen, userEvent } from 'test-utils';
import { BrowserRouter } from 'react-router-dom';

// testable
import SubmitForm from 'merchant/views/Settings/Configuration/Questionnaire/SubmitForm';

describe('Test <SubmitForm /> component', () => {
  test('should render without breaking', () => {
    render(
      <BrowserRouter>
        <SubmitForm />
      </BrowserRouter>,
    );
    expect(screen.getByText('Submit Form')).toBeInTheDocument();
  });

  test('should toggle checkbox', async () => {
    render(
      <BrowserRouter>
        <SubmitForm />
      </BrowserRouter>,
    );

    const checkbox = screen.getByRole('checkbox');
    await userEvent.click(checkbox);

    expect(checkbox).toBeChecked();
  });
});
