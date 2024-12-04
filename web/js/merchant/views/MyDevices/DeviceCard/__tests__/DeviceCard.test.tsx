import React from 'react';

import { screen, render, waitFor, userEvent } from 'common/services/test/test-utils';
import DeviceCard from 'merchant/views/MyDevices/DeviceCard';
import { MOCK_DEVICE } from '../../mocks/fixtures';

const App = (props) => <DeviceCard {...props} />;

const mockDevice = MOCK_DEVICE[0];
describe('Device Card', () => {
  test('should render my devices page correctly', () => {
    render(<App device={mockDevice} />, {});
    expect(screen.getByText(/J&K Soundbox/i)).toBeInTheDocument();
  });

  test('Should render correct network strength', () => {
    render(
      <App
        device={{
          ...mockDevice,
          stats: {
            ...mockDevice.stats,
            network_strength: 80,
          },
        }}
      />,
      {},
    );
    expect(screen.getByText('High')).toBeInTheDocument();
  });

  test('Should render language dropdown on click', async () => {
    render(<App device={mockDevice} />, {});
    const languageBtn = screen.getByRole('button');
    expect(languageBtn).toBeInTheDocument();

    userEvent.click(languageBtn);

    await waitFor(() => {
      expect(screen.getAllByRole('menuitem')).toHaveLength(4);
    });
  });
});
