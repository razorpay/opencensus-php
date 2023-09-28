import MagicSettings from '..';
import { screen, render } from 'test-utils';
import { waitFor } from '@testing-library/dom';

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
