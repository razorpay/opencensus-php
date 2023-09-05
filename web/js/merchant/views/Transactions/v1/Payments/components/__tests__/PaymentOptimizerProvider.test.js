import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import PaymentOptimizerProvider from 'merchant/views/Transactions/v1/Payments/components/PaymentOptimizerProvider';
import { render, screen } from 'test-utils';

describe('PaymentOptimizerProvider', () => {
  const App = (props) => {
    return <PaymentOptimizerProvider {...props} />;
  };

  test('should not render payment optimizer provider details without terminal_id', () => {
    render(<App />);
    expect(screen.getByText('--')).toBeInTheDocument();
  });

  test('should render payment optimizer provider details when terminalProviders & terminal_id are present', () => {
    render(
      <App
        terminal_id="terminal name"
        terminalProviders={[
          {
            Terminal_id: 'terminal name',
            Provider_name: 'provider name',
          },
        ]}
      />,
    );
    expect(screen.getByText('provider name')).toBeInTheDocument();
    expect(screen.getByText('Provider details')).toBeInTheDocument();
  });

  test('should render payment optimizer provider details when settled_by is present', () => {
    render(<App terminal_id="terminal name" settled_by="settled by name" />);
    expect(screen.getByText('Settled By Name')).toBeInTheDocument();
  });

  test('should render payment optimizer provider details when settled_by is Razorpay', () => {
    render(<App terminal_id="terminal name" settled_by="Razorpay" />);
    expect(screen.getByText('Razorpay')).toBeInTheDocument();
  });

  test('should render payment optimizer provider details when terminal_id is Razorpay', () => {
    render(<App terminal_id="Razorpay" />);
    expect(screen.getByText('Razorpay')).toBeInTheDocument();
  });

  test('should render payment optimizer provider details when terminal_id is Razorpay & isTableView', () => {
    render(<App terminal_id="Razorpay" isTableView />);
    expect(screen.getByText('Razorpay')).toBeInTheDocument();
  });

  test('should render payment optimizer provider details when there is detail view & external link', () => {
    render(
      <App
        isDetailView
        hideExternalLink={false}
        terminal_id="terminal name"
        terminalProviders={[
          {
            Terminal_id: 'terminal name',
            Provider_name: 'provider name',
          },
        ]}
      />,
    );
    expect(screen.getByText('provider name')).toBeInTheDocument();
  });

  test('should render shorten provider name till 20 characters when there is detail view & external link & provider name length is more than 23', () => {
    const terminalProviders = [
      {
        Terminal_id: 'terminal name',
        Provider_name: 'provider name 1234567890 abcxyz 0987654321',
      },
    ];
    render(
      <App
        isDetailView
        hideExternalLink={false}
        terminal_id="terminal name"
        terminalProviders={terminalProviders}
      />,
    );
    expect(
      screen.getByText(`${terminalProviders[0].Provider_name.substr(0, 20)}...`),
    ).toBeInTheDocument();
  });
});
