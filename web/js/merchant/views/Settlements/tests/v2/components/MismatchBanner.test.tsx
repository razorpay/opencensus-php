import React from 'react';
import { MismatchBanner } from 'merchant/views/Settlements/v2/components/MismatchBanner';
import { delay, render, screen } from 'test-utils';

type AppProps = { totalAmount: number; calculatedAmounts: number; gatewayName: string };

const GATEWAY_NAME = 'payu';

const App: React.FC<AppProps> = (props: AppProps) => {
  const { totalAmount, calculatedAmounts, gatewayName } = props;
  return (
    <MismatchBanner
      totalAmount={totalAmount}
      calculatedAmounts={calculatedAmounts}
      gatewayName={gatewayName}
    />
  );
};

test('Should render banner heading', async () => {
  render(<App totalAmount={10000} calculatedAmounts={7000} gatewayName={GATEWAY_NAME} />);

  await delay();

  expect(screen.getByText(/Settlement mismatch of/i)).toBeInTheDocument();
});

test('Should render banner description', async () => {
  render(<App totalAmount={10000} calculatedAmounts={7000} gatewayName={GATEWAY_NAME} />);

  await delay();

  expect(screen.getByText(/Razorpay Optimizer has a log of/i)).toBeInTheDocument();
  expect(screen.getByText(/but, we fetched an amount of/i)).toBeInTheDocument();
  expect(screen.getByText(/Razorpay Optimizer has a log of/i)).toBeInTheDocument();
  expect(screen.getByText(/dashboard for mismatched amounts./i)).toBeInTheDocument();
});
