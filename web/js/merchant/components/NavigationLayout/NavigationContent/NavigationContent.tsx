import React, { useMemo, Suspense } from 'react';
import GrowthPage from 'merchant/components/NavigationLayout/ProductPages/GrowthPage';
import AccessDeniedPage from 'merchant/components/NavigationLayout/ProductPages/AccessDenied';
import ErrorPage from 'merchant/components/NavigationLayout/ProductPages/ErrorPage';
import useConnectedNavigationStore from '../navigationStore';
import withConnectedLayout from './withConnectedLayout';
import { Skeleton } from './Skeleton/Skeleton';
import { Spinner } from '@razorpay/blade/components';

const Payments = React.lazy(() => import('./Payments/Payments'));
const Banking = React.lazy(() => import('./Banking/Banking'));
const Partner = React.lazy(() => import('./Partner/Partner'));
const Payroll = React.lazy(() => import('./Payroll/Payroll'));
const BillMe = React.lazy(() => import('./BillMe/BillMe'));
const Rize = React.lazy(() => import('./Rize/Rize'));

const PaymentsWithLayout = withConnectedLayout(Payments);
const BankingWithLayout = withConnectedLayout(Banking);
const PartnerWithLayout = withConnectedLayout(Partner);
const PayrollWithLayout = withConnectedLayout(Payroll);
const BillMeWithLayout = withConnectedLayout(BillMe);
const RizeWithLayout = withConnectedLayout(Rize);

const GrowthPageWithLayout = withConnectedLayout(GrowthPage);
const AccessDeniedWithLayout = withConnectedLayout(AccessDeniedPage);
const ErrorPageWithLayout = withConnectedLayout(ErrorPage);

const MicroFrontendLoader: React.FC<{ renderFullPageView?: boolean } & Record<string, any>> = (
  props,
) => {
  const { selectedProduct } = useConnectedNavigationStore();
  const product = selectedProduct?.product;
  const alias = product?.alias;
  const { renderFullPageView = false } = props;

  const { Component, componentProps } = useMemo(() => {
    if (!product) return { Component: null, componentProps: {} };

    const comps = product.components || [];

    // Handle special pages (no layout)
    if (comps.length > 0) {
      const mainComponent = comps[0];
      if (mainComponent.type === 'growth_page') {
        return {
          Component: GrowthPageWithLayout,
          componentProps: {
            isConnectedFullPageView: true,
            title: mainComponent.title,
            description: mainComponent.description,
            actions: mainComponent.actions,
            imageSrc: mainComponent.background_img,
          },
        };
      }
      if (mainComponent.type === 'access_denied_page') {
        return {
          Component: AccessDeniedWithLayout,
          componentProps: {
            isConnectedFullPageView: true,
            title: mainComponent.title,
            description: mainComponent.description,
          },
        };
      }
    } else {
      // No components defined
      if (alias === 'payments_top_navigation_item') {
        // Payments fallback
        return {
          Component: renderFullPageView ? Payments : PaymentsWithLayout,
          componentProps: {},
        };
      } else {
        return {
          Component: ErrorPageWithLayout,
          componentProps: {
            isConnectedFullPageView: true,
            title: 'It’s not you it’s us.',
            description:
              'We are unable to fetch the required information right now. Request you to retry after sometime',
          },
        };
      }
    }

    // Normal products
    let WrappedComponent;
    switch (alias) {
      case 'payments_top_navigation_item':
        WrappedComponent = renderFullPageView ? Payments : PaymentsWithLayout;
        break;
      case 'banking_top_navigation_item':
        WrappedComponent = renderFullPageView ? Banking : BankingWithLayout;
        break;
      case 'partners_top_navigation_item':
        WrappedComponent = renderFullPageView ? Partner : PartnerWithLayout;
        break;
      case 'payroll_top_navigation_item':
        WrappedComponent = renderFullPageView ? Payroll : PayrollWithLayout;
        break;
      case 'billme_top_navigation_item':
        WrappedComponent = renderFullPageView ? BillMe : BillMeWithLayout;
        break;
      case 'rize_top_navigation_item':
        WrappedComponent = renderFullPageView ? Rize : RizeWithLayout;
        break;
      default:
        WrappedComponent = ErrorPage;
        break;
    }

    return { Component: WrappedComponent, componentProps: {} };
  }, [product, alias, renderFullPageView]);

  if (!Component) {
    return <Skeleton />;
  }

  return (
    <Suspense fallback={<Spinner accessibilityLabel="nav-component" />}>
      <Component {...props} {...componentProps} />
    </Suspense>
  );
};

export default MicroFrontendLoader;
