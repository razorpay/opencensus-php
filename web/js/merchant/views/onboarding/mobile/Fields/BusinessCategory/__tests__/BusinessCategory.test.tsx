import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import BusninessCategory from 'merchant/views/onboarding/mobile/Fields/BusinessCategory/index';
import { cleanup, fireEvent, render, screen, waitFor } from 'test-utils';

interface AppProps {
  value: string;
}

const App: React.FC<AppProps> = ({ value }) => {
  return <BusninessCategory value={value} />;
};

afterEach(() => {
  cleanup();
});

test('should render bottomsheet with ecommerce panel opened initially', async () => {
  render(<App value="" />, {});
  fireEvent.click(screen.getByTestId('ds-text'));
  await waitFor(() => {
    expect(screen.getByPlaceholderText('Search Business Category')).toBeInTheDocument();
    expect(screen.getByText('ecommerce')).toBeInTheDocument();
    expect(
      screen.getByText('Computers, Computer Peripheral Equipment, Software'),
    ).toBeInTheDocument();
  });
});

test('should render the selected value with related panel opened only on Search', async () => {
  render(<App value="" />, {});
  fireEvent.click(screen.getByTestId('ds-text'));
  await waitFor(() => {
    fireEvent.change(screen.getByPlaceholderText('Search Business Category'), {
      target: { value: 'Technical' },
    });
  });

  await waitFor(() => expect(screen.getByRole('loader')).toBeInTheDocument());
  await waitFor(() => {
    expect(screen.queryByText('ecommerce')).not.toBeInTheDocument();
    expect(screen.getByText('it_and_software')).toBeInTheDocument();
    expect(screen.getByText('Technical Support')).toBeInTheDocument();
  });
});
