// TemplateSelectionV2.test.tsx
import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import { analyticsTrack } from 'common/utils/analytics';
import track from 'merchant/views/PaymentPages/PaymentPages/List/track';
import { StorefrontIcon } from '@razorpay/blade/components';
import TemplateSelectionV2 from '../CreateEdit/TemplateSelectionV2';
import { FeatureList } from '../CreateEdit/TemplateSelectionV2/PageComponents';
import { PAYMENT_PAGES_TYPES } from '../CreateEdit/TemplateSelectionV2/PageConfig';

jest.mock('common/utils/analytics', () => ({
  analyticsTrack: jest.fn(),
}));

jest.mock('common/utils/rzp-utils', () => ({
  getCommonAnalyticsProperties: jest.fn(() => ({ user_id: 'test-user' })),
}));

jest.mock('merchant/views/PaymentPages/PaymentPages/List/track', () => ({
  selectTemplatePageLoaded: jest.fn(),
  selectStorefrontPage: jest.fn(),
}));

const mockHistoryPush = jest.fn();
jest.mock('@libs/web-nexus/common/deprecated/withRouter', () => ({
  withRouter: (Component) => {
    const WithRouterComponent = (props) => {
      return <Component {...props} history={{ push: mockHistoryPush }} location={{}} match={{}} />;
    };
    WithRouterComponent.displayName = `withRouter(${Component.displayName || Component.name})`;
    return WithRouterComponent;
  },
}));

beforeEach(() => {
  jest.clearAllMocks();
});

describe('TemplateSelectionV2 Component Tests', () => {
  const mockOnClick = jest.fn();
  describe('TemplateSelector', () => {
    const defaultProps = {
      handlePageType: jest.fn(),
      isMobile: false,
    };

    test('renders correctly with desktop layout', () => {
      render(<TemplateSelectionV2 {...defaultProps} />);

      expect(screen.getByText('Select a page according to your needs')).toBeInTheDocument();

      expect(screen.getByText('Razorpay Webstore')).toBeInTheDocument();
      expect(screen.getByText('Best for businesses selling multiple products')).toBeInTheDocument();

      expect(screen.getByText('Payment Page')).toBeInTheDocument();
      expect(
        screen.getByText('Best for businesses selling a single product or service'),
      ).toBeInTheDocument();

      expect(
        screen.getByText('Online store with seamless payment acceptance - no coding required!'),
      ).toBeInTheDocument();
      expect(
        screen.getByText('Ideal for selling multiple products with a catalog'),
      ).toBeInTheDocument();
      expect(track.selectTemplatePageLoaded).toHaveBeenCalledTimes(1);
    });

    test('renders correctly with mobile layout', () => {
      render(<TemplateSelectionV2 {...defaultProps} isMobile={true} />);

      expect(screen.getByText('Select a page according to your needs')).toBeInTheDocument();
      expect(screen.getByText('Razorpay Webstore')).toBeInTheDocument();
      expect(screen.getByText('Payment Page')).toBeInTheDocument();
      expect(screen.getByText('Online store with checkout—no coding needed!')).toBeInTheDocument();
    });

    test('close button redirects to payment pages', () => {
      render(<TemplateSelectionV2 {...defaultProps} />);

      const closeButton = screen.getByLabelText('header-back-btn');
      fireEvent.click(closeButton);

      expect(mockHistoryPush).toHaveBeenCalledWith('/paymentpages');
    });

    test('clicking storefront button triggers correct handlers and analytics', () => {
      render(<TemplateSelectionV2 {...defaultProps} />);

      const createButtons = screen.getByText('Create Razorpay Webstore');
      fireEvent.click(createButtons);

      expect(defaultProps.handlePageType).toHaveBeenCalledWith(PAYMENT_PAGES_TYPES.storefront);
      expect(track.selectStorefrontPage).toHaveBeenCalledWith({ product_template: "storefront" });
    });

    test('clicking payment page button triggers correct handlers and analytics', () => {
      render(<TemplateSelectionV2 {...defaultProps} />);

      const createButtons = screen.getByText('Create Payment Page');
      fireEvent.click(createButtons);

      expect(defaultProps.handlePageType).toHaveBeenCalledWith(PAYMENT_PAGES_TYPES.payment_page);
      expect(analyticsTrack).toHaveBeenCalledWith({
        objectName: 'Payment page',
        actionName: 'clicked',
        screen: 'Select page of your choice',
        properties: expect.objectContaining({
          user_id: 'test-user',
          product_template: 'page',
        }),
      });
    });
  });

  describe('FeatureList Component', () => {
    const mockFeatures = [
      {
        icon: <StorefrontIcon data-testid="storefront-icon" />,
        textDesktop: 'Feature 1 desktop',
        textMobile: 'Feature 1 mobile',
      },
      {
        icon: <StorefrontIcon data-testid="storefront-icon" />,
        textDesktop: 'Feature 2 desktop',
        textMobile: 'Feature 2 mobile',
      },
      {
        icon: <StorefrontIcon data-testid="storefront-icon" />,
        textDesktop: 'Feature 3 desktop',
        textMobile: 'Feature 3 mobile',
      },
    ];

    test('renders all features correctly', () => {
      render(<FeatureList features={mockFeatures} />);
      expect(screen.getByText('Feature 1 desktop')).toBeInTheDocument();
      expect(screen.getByText('Feature 2 desktop')).toBeInTheDocument();
      expect(screen.getByText('Feature 3 desktop')).toBeInTheDocument();

      const icons = screen.getAllByTestId('storefront-icon');
      expect(icons.length).toBe(3);
    });
  });
});
