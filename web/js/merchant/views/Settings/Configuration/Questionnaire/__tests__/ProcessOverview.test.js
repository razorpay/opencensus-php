// utils
import { render, screen, userEvent } from 'test-utils';

// testable
import ProcessOverview from 'merchant/views/Settings/Configuration/Questionnaire/ProcessOverview';

describe('Test <ProcessOverview /> component', () => {
  test('should render without breaking', () => {
    render(<ProcessOverview />);
    expect(screen.getByText('Process Overview')).toBeInTheDocument();
  });

  test('should toggle accordions', async () => {
    render(<ProcessOverview />);
    const el = screen.getByText('Why Another Questionnaire?');

    await userEvent.click(el);
    expect(el.parentElement).toHaveClass('open');

    await userEvent.click(el);
    expect(el.parentElement).not.toHaveClass('open');
  });
});
