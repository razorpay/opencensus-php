import MagicSettings from '..';
import { screen, render, waitFor } from 'test-utils';

describe('magic settings', () => {
  test('render magic settings', async () => {
    render(<MagicSettings />, {
      initialState: {},
    });
    await waitFor(() => {
      expect(screen.getByText(/^Platform?/i)).toBeInTheDocument();
    });
  });
});
