import { render, screen, userEvent, waitFor } from 'test-utils';
import PlatformSubText from 'merchant/views/MagicCheckout/Settings/components/PlatformSubText';

const initProps = {
  nested_view_type: 'settings',
  shop_id: 'test',
  updatePage: jest.fn(),
  platform: 'woocommerce',
  shipping_info: 'https://testingRzp/wp-json.com',
  merchantId: 'test_123',
  one_click_checkout: true,
  user: {
    isMagicWoocEnabled: true,
    isShopifyMagicEnabled: false,
  },
};

const App = (props) => <PlatformSubText {...initProps} {...props} />;

describe('testing platform subtext component', () => {
  test('magic checkout toggle should be visible for wooc if enabled', async () => {
    render(<App />);
    await waitFor(() => {
      expect(screen.getByText(/^Magic checkout?/i)).toBeInTheDocument();
    });

    await userEvent.click(screen.getByText('Edit'));
    await waitFor(() => {
      expect(initProps.updatePage).toHaveBeenCalled();
    });
  });

  test('magic checkout toggle should be visible for shopify if enabled', async () => {
    const customProps = {
      platform: 'shopify',
      shipping_info: '',
      user: {
        isMagicWoocEnabled: false,
        isShopifyMagicEnabled: true,
      },
    };
    render(<App {...customProps} />);
    await waitFor(() => {
      expect(screen.queryByText(/^Magic checkout?/i)).toBeInTheDocument();
    });
  });

  test('should be able to click on edit cta', async () => {
    const customProps = {
      shop_id: 'test.myshopify.com',
      platform: 'shopify',
      user: {
        isMagicWoocEnabled: true,
        isShopifyMagicEnabled: true,
      },
    };

    render(<App {...customProps} />);
    const editCta = screen.getByTestId('platform-edit-icon');
    await userEvent.click(editCta);
    await waitFor(() => {
      expect(initProps.updatePage).toHaveBeenCalled();
    });
  });

  test("shouldn't show select platform if platform is not selected", async () => {
    const customProps = {
      nested_view_type: 'platform_selection',
      shop_id: 'test.myshopify.com',
      platform: 'shopify',
      user: {
        isMagicWoocEnabled: true,
        isShopifyMagicEnabled: true,
      },
    };

    render(<App {...customProps} />);

    await waitFor(() => {
      expect(
        screen.getByText('Select the platform of your ecommerce website.'),
      ).toBeInTheDocument();
    });
  });

  test("shouldn't show app toggle if multiple apps are not installed", () => {
    const customProps = {
      nested_view_type: 'settings',
      shop_id: 'test.myshopify.com',
      platform: 'shopify',
      user: {
        isMagicWoocEnabled: true,
        isShopifyMagicEnabled: true,
      },
      apps_installed: ['rcod'],
      dashboard_view: 'rcod',
    };

    render(<App {...customProps} />);

    expect(screen.queryByText('Toggle checkout platform')).not.toBeInTheDocument();
  });

  test('should show app toggle if multiple apps are installed', () => {
    const customProps = {
      nested_view_type: 'settings',
      shop_id: 'test.myshopify.com',
      platform: 'shopify',
      user: {
        isMagicWoocEnabled: true,
        isShopifyMagicEnabled: true,
      },
      apps_installed: ['rcod', 'magic_checkout'],
      dashboard_view: 'rcod',
    };

    render(<App {...customProps} />);

    expect(screen.getByText('Toggle checkout platform:')).toBeInTheDocument();
  });
});
