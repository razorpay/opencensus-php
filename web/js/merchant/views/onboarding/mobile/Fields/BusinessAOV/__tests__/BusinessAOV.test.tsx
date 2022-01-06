import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import BusninessAOV from '../index';
import { cleanup, fireEvent, render, screen, delay } from 'test-utils';

interface AppProps {
  value: string;
}

const App: React.FC<AppProps> = ({ value }) => {
  return <BusninessAOV value={value} />;
};

afterEach(() => {
  cleanup();
});

test('should render bottomsheet with ecommerce panel opened initially', async () => {
  render(<App value="" />, {});
  const testId = screen.getAllByTestId('ds-text');
  fireEvent.click(testId[0]);
  await delay(4000);
  expect(screen.getByText('Average Order Value')).toBeInTheDocument();
  expect(screen.getByText('SELECT AVERAGE ORDER VALUE')).toBeInTheDocument();
  expect(screen.getByText('₹ 151 - ₹ 300')).toBeInTheDocument();
  expect(screen.getByText('More than ₹ 1,00,000')).toBeInTheDocument();
});
