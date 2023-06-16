import { render, screen, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import CredentialsForm from 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/Woocommerce/component/CredentialsForm';

const INIT_STATE = {
  magic_settings: {
    shipping_info: 'https://test.com/wp-json/1cc/v1/shipping/shipping-info',
  },
};

const INIT_PROPS = {
  closeModal: jest.fn(),
  platform: 'woocommerce',
  submitCredentials: jest.fn(),
  modalDesc: 'woocommerce credentials modal',
};

const renderApp = ({ state, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...INIT_STATE, ...state })}>
      <CredentialsForm {...props} />
    </Provider>,
  );
};

describe('testing woocommerce credential form modal', () => {
  test('component should render properly', () => {
    renderApp({ ...INIT_PROPS });
    expect(screen.getByText(/^woocommerce credentials modal?/)).toBeInTheDocument();
  });

  test('should be able to click on submit cta', async () => {
    renderApp({ ...INIT_PROPS, customCloseModal: jest.fn() });

    const consumerKeyInput = screen.getByRole('textbox', {
      name: 'Consumer key *',
    });

    const consumerSecretInput = screen.getByRole('textbox', {
      name: 'Consumer secret *',
    });

    const submitCta = screen.getByRole('button', {
      name: 'Submit',
    });

    //checking if the submit cta is disabled or not if the inputs are empty
    expect(submitCta.disabled).toBe(true);

    await userEvent.type(consumerKeyInput, 'test_key');
    await userEvent.type(consumerSecretInput, 'test_secret');

    expect(consumerKeyInput.value).toBe('test_key');
    expect(consumerSecretInput.value).toBe('test_secret');

    //checking if the submit cta is enabled or not if the inputs are not empty
    expect(submitCta.disabled).toBe(false);
    await userEvent.click(submitCta);
  });
});
