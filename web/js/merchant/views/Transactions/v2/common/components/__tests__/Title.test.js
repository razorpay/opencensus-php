import { screen } from 'test-utils';
import { renderApp } from './fixtures/mocks/Title';

describe('Title', () => {
  test('should render children correctly with the specified styles', () => {
    renderApp({ children: 'Test Title' });
    const titleElement = screen.getByText('Test Title');
    expect(titleElement).toHaveStyle(`
      font-weight: 700;
    `);
  });

  test('should render custom children', () => {
    renderApp({ children: 'Custom Title' });
    const customTitleElement = screen.getByText('Custom Title');
    expect(customTitleElement).toBeInTheDocument();
  });
});
