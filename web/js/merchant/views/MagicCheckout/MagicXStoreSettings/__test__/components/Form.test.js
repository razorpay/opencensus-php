import { Provider } from 'react-redux';

import { storeWithInitialState } from 'merchant/store';
import Form from 'merchant/views/MagicCheckout/MagicXStoreSettings/components/Form';
import { screen, render } from 'test-utils';

const initState = {
  magic_settings: {
    shop_plan_name: 'basic',
    sopc_metafields: {
      status: 'live',
      integrations: {
        recurpay: {
          enabled: false,
        },
      },
      cart_page_login: false,
      permalinks_flow: true,
      is_email_optional: true,
      is_email_mandatory: false,
      is_login_mandatory: true,
      enable_native_click: false,
      merchant_theme_color: '#000000',
    },
  },
};

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...initState, ...state })}>
      <Form {...props} />
    </Provider>
  );
};

describe('MagicX Settings Form Component', () => {
  test('should render specific fields for all', async () => {
    render(<App />);

    const appEnabledField = await screen.findByText('Enable Checkout360');
    const emailField = await screen.findByText('Email Field');
    const modalColorField = await screen.findByText('Theme Color');
    const loginMandatoryField = await screen.findByText('Mandatory OTP');

    expect(appEnabledField).toBeInTheDocument();
    expect(emailField).toBeInTheDocument();
    expect(modalColorField).toBeInTheDocument();
    expect(loginMandatoryField).toBeInTheDocument();
  });

  //For Checkout360 flow , we wont be supporting checkout type as of initial rollout. Skipped but not removed incase needed for future versions
  test.skip('should render flow type field only for plus plan merchants', async () => {
    const customState = {
      magic_settings: {
        shop_plan_name: 'shopify_plus',
      },
    };
    render(<App state={customState} />);

    const flowTypeField = await screen.findByText('Checkout Type');
    expect(flowTypeField).toBeInTheDocument();
  });

  test('should render the fields with proper values on initial load', () => {
    const customState = {
      magic_settings: {
        shop_plan_name: 'shopify_plus',
        sopc_metafields: {
          status: 'live',
          integrations: {
            recurpay: {
              enabled: false,
            },
          },
          cart_page_login: false,
          is_email_optional: true,
          is_email_mandatory: false,
          is_login_mandatory: true,
          enable_native_click: false,
          merchant_theme_color: '#FFFFFF',
        },
      },
    };
    const { container } = render(<App state={customState} />);

    const appEnabledToggle = container.querySelector(`button[name="appEnabled"]`);
    const emailFieldDropdown = container.querySelector(`select[name="emailField"]`);
    const isLoginMandatoryToggle = container.querySelector(`button[name="isLoginMandatory"]`);
    const themeColor = container.querySelector(`input[name="themeColor"]`);

    expect(appEnabledToggle).toHaveValue('true');
    expect(emailFieldDropdown).toHaveValue('optional');
    expect(isLoginMandatoryToggle).toHaveValue('true');
    expect(themeColor).toHaveValue('#ffffff');
  });
});
