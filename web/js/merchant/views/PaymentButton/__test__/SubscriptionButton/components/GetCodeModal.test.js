import React from 'react';
import { render, screen, waitFor, userEvent } from 'test-utils';
import GetCodeModal from 'merchant/views/PaymentButton/SubscriptionButton/components/GetCodeModal';
import { storeWithInitialState } from 'merchant/store';
import { Provider } from 'react-redux';

jest.mock('common/ui/Clipboard/Custom', () => ({
  __esModule: true,
  default: jest.fn(({ children }) => <div data-testid="mocked-clipboard">{children}</div>),
}));

describe('GetCodeModal', () => {
  const paymentButton = {
    id: '123',
    settings: {
      payment_button_theme: 'dark',
    },
  };
  const closeModal = jest.fn();
  const onCodeCopy = jest.fn();
  const onClickSeeDocumentation = jest.fn();

  beforeEach(() => {
    closeModal.mockReset();
    onCodeCopy.mockReset();
    onClickSeeDocumentation.mockReset();
  });

  const initialState = {
    session: {
      user: {
        current: 'user123',
      },
      mode: 'test',
    },
  };

  const App = ({ initialState, ...rest }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <GetCodeModal
          paymentButton={paymentButton}
          closeModal={closeModal}
          onCodeCopy={onCodeCopy}
          onClickSeeDocumentation={onClickSeeDocumentation}
          {...rest}
        />
      </Provider>
    );
  };

  test('renders the loading spinner when the payment button settings are not available', () => {
    render(
      <App
        initialState={initialState}
        paymentButton={{
          ...paymentButton,
          settings: null,
        }}
      />,
    );
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
  });

  test('renders the copy button when the payment button settings are available', async () => {
    render(<App initialState={initialState} />);
    const copyBtn = screen.getByRole('button', {
      name: 'COPY CODE',
    });
    expect(copyBtn).toBeInTheDocument();
    await userEvent.click(copyBtn);

    await waitFor(() => {
      expect(onCodeCopy).toHaveBeenCalled();
    });
  });

  test('renders the copy button when the payment button settings are available', () => {
    render(<App initialState={initialState} />);
    const textBox = screen.getByRole('textbox');
    expect(textBox).toBeInTheDocument();
  });

  test('renders the documentation link when no children are provided', () => {
    const { getByText } = render(<App />);

    expect(getByText('How to use this code?')).toBeInTheDocument();
    expect(getByText('See documentation')).toBeInTheDocument();
  });

  test('does not renders the documentation link when children are provided', () => {
    render(
      <App>
        <p>child component</p>
      </App>,
    );
    expect(screen.queryByText('How to use this code?')).not.toBeInTheDocument();
    expect(screen.queryByText('See documentation')).not.toBeInTheDocument();
  });

  test('calls the onClickSeeDocumentation function when the documentation link is clicked', async () => {
    const { getByText } = render(<App />);

    await userEvent.click(getByText('See documentation'));

    expect(onClickSeeDocumentation).toHaveBeenCalled();
  });

  test('renders the Back to Dashboard button', () => {
    const { getByText } = render(<App />);

    expect(getByText('Back to Dashboard')).toBeInTheDocument();
  });

  test('calls the closeModal function when the Back to Dashboard button is clicked', async () => {
    const { getByText } = render(<App />);
    await userEvent.click(getByText('Back to Dashboard'));
    expect(closeModal).toHaveBeenCalled();
  });
});
