import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, waitFor, userEvent } from 'test-utils';
import NavLinkItem from 'merchant/components/SidebarV2/components/NavLinkItem';
import * as analytics from 'common/utils/analytics';

const paymentLinksInfo = {
  title: 'Payment Links',
  product_id: 'payment_links',
  routes: {
    payment_links: '/paymentlinks',
  },
  section: 'Payment Products',
};

const transactionsLinksInfo = {
  title: 'Transactions',
  product_id: 'transactions',
  routes: {
    transactions: '/payments',
  },
};

const defaultProps = {
  additionalCondition: () => true,
  ...transactionsLinksInfo,
};

describe('NavLinkItem', () => {
  const renderApp = ({ props, renderOptions = {} } = {}) =>
    render(<NavLinkItem {...defaultProps} {...props} />, renderOptions);
  const analyticsTrackMock = jest.spyOn(analytics, 'analyticsTrack');

  test('should render title in navlink item', async () => {
    renderApp();
    await waitFor(() => {
      expect(screen.getByText(transactionsLinksInfo.title)).toBeInTheDocument();
    });
  });

  test('should render link and redirect when clicked', async () => {
    const { history } = renderApp();

    const link = await screen.findByRole('link');
    expect(link).toBeInTheDocument();
    expect(link).toHaveAttribute('href', '/payments');
    await userEvent.click(link);
    expect(history.location.pathname).toEqual('/payments');
  });

  describe('Analytics on clicking link', () => {
    test('should be called along with link title as section name', async () => {
      renderApp({
        renderOptions: {
          initialEntries: ['/profile'],
          path: '/profile',
        },
      });

      const link = await screen.findByRole('link');
      await userEvent.click(link);

      expect(analyticsTrackMock).toHaveBeenCalledWith({
        objectName: 'sidebar',
        actionName: 'clicked',
        screen: 'My Account',
        toCleverTap: true,
        properties: {
          clickedElement: transactionsLinksInfo.title,
          section: transactionsLinksInfo.title,
          location: 'sidebar',
          sidebar: 'v2',
        },
      });
    });

    test('should be called along with section name when section name exists', async () => {
      renderApp({
        props: paymentLinksInfo,
        renderOptions: {
          initialEntries: ['/profile'],
          path: '/profile',
        },
      });

      const link = await screen.findByRole('link');
      await userEvent.click(link);

      expect(analyticsTrackMock).toHaveBeenCalledWith({
        objectName: 'sidebar',
        actionName: 'clicked',
        screen: 'My Account',
        toCleverTap: true,
        properties: {
          clickedElement: paymentLinksInfo.title,
          section: paymentLinksInfo.section,
          location: 'sidebar',
          sidebar: 'v2',
        },
      });
    });
  });

  test('should render tags when available', async () => {
    renderApp({
      props: {
        tags: ['NEW'],
        routes: {},
      },
    });
    await waitFor(() => {
      expect(screen.getByText('NEW')).toBeInTheDocument();
    });
  });

  test('should not render new tag even if other tags are added', () => {
    renderApp({
      props: {
        tags: ['test'],
      },
    });
    expect(screen.queryByText('New')).not.toBeInTheDocument();
  });

  test('should render international payment link with type linkButton', () => {
    renderApp({
      props: {
        title: 'International Payments',
        product_id: 'internationalPaymentsBtn',
        type: 'linkButton',
        routes: {
          internationalPaymentsBtn: '/payment-methods?instrument=international',
        },
      },
    });

    expect(screen.getByText('International Payments')).toBeInTheDocument();
    expect(screen.getByRole('link')).toHaveAttribute(
      'href',
      '/payment-methods?instrument=international',
    );
  });

  test('should render image when image prop is passed instead of logo', () => {
    const dummyUrl = 'https://dummyurl.com/image.png';
    renderApp({
      props: {
        title: 'Magic Konnect',
        product_id: 'magic_konnect',
        image: dummyUrl,
        routes: {
          magic_konnect: '/magic-konnect',
        },
      },
    });

    expect(screen.getByAltText('Magic Konnect')).toBeInTheDocument();
    const imageElement = screen.getByAltText('Magic Konnect');
    expect(imageElement).toBeInTheDocument();
    expect(imageElement).toHaveAttribute('src', dummyUrl);
    expect(document.querySelector('i')).not.toBeInTheDocument();
  });

  test('should render icon only when image is not passed in prop', () => {
    renderApp({
      props: {
        title: 'Magic Konnect',
        product_id: 'magic_konnect',
        routes: {
          magic_konnect: '/magic-konnect',
        },
      },
    });

    expect(screen.getByText('Magic Konnect')).toBeInTheDocument();
    expect(screen.queryByAltText('Magic Konnect')).not.toBeInTheDocument();
  });

  test('should render `Magic Checkout` with title `Checkout360` if user has completed C360 onboarding', () => {
    renderApp({
      props: {
        title: 'Magic Checkout',
        product_id: 'magic_checkout',
        user: {
          isC360OnboardingCompleted: true,
        },
      },
    });

    expect(screen.getByText('Checkout360')).toBeInTheDocument();
    expect(screen.queryByAltText('Magic Checkout')).not.toBeInTheDocument();
  });

  test('should render `Magic Checkout` with title `Magic Checkout` if user has not completed C360 onboarding', () => {
    renderApp({
      props: {
        title: 'Magic Checkout',
        product_id: 'magic_checkout',
        user: {
          isC360OnboardingCompleted: false,
        },
      },
    });

    expect(screen.getByText('Magic Checkout')).toBeInTheDocument();
    expect(screen.queryByAltText('Checkout360')).not.toBeInTheDocument();
  });
});
