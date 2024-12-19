import React, { Suspense, useEffect, useMemo } from 'react';
import { withRouter } from 'common/deprecated/withRouter';
import { Route, NavLink, Routes } from 'react-router-dom';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import TestModeBanner from 'merchant/components/TestModeBanner';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import DashboardBanner from 'common/ui/DashboardBanner';
import Breadcrumb from 'common/components/Breadcrumb';
import {
  accountAndSettingsLink,
  ROUTE_MAP,
} from 'merchant/views/AccountAndSettings/constants/constants';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import {
  StyledDivider,
  StyledHeader,
  StyledTabContainer,
} from 'merchant/views/AccountAndSettings/styled';
import lazy from 'merchant/routes/LazyLoader';
import { connect } from 'react-redux';
import {
  setInstrument,
  clearIntermediateInstrument,
  clearLeafInstrument,
  fetchMerchantInstruments,
  fetchRequestedInstruments,
  getDiscrepanciesCategories,
} from 'merchant/reducers/instrumentRequests';
import { analyticsTrackWithUserInfo } from 'common/utils/analytics';
import { PaymentMethodsTabsRoutesConfig } from 'merchant/views/AccountAndSettings/PaymentMethods/constants';
import { showNotification } from 'merchant_common/reducers/notifications';
import SectionShimmer from 'merchant/views/AccountAndSettings/PaymentMethods/components/Shimmer';
import Loader from 'common/components/Loader';
import {
  InstrumentListItem,
  InstrumentRequestsReducerState,
  InstrumentsList,
  Store,
  User,
} from 'common/typings';
import { bindActionCreators } from 'redux';
import qs from 'query-string';
import { RouteGuard } from 'merchant/components/ShowWhen';

const Cards = lazy(
  () =>
    import(
      /* webpackChunkName: "PaymentMethods__Cards" */ 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/Cards'
    ),
);
const Emi = lazy(
  () =>
    import(
      /* webpackChunkName: "PaymentMethods__Emi" */ 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/Emi'
    ),
);
const International = lazy(
  () =>
    import(
      /* webpackChunkName: "PaymentMethods__International" */ 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International'
    ),
);
const Netbanking = lazy(
  () =>
    import(
      /* webpackChunkName: "PaymentMethods__Netbanking" */ 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/Netbanking'
    ),
);
const Paylater = lazy(
  () =>
    import(
      /* webpackChunkName: "PaymentMethods__Paylater" */ 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/Paylater'
    ),
);
const UpiQR = lazy(
  () =>
    import(
      /* webpackChunkName: "PaymentMethods__UpiQR" */ 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/UpiQR'
    ),
);
const Wallet = lazy(
  () =>
    import(
      /* webpackChunkName: "PaymentMethods__Wallet" */ 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/Wallet'
    ),
);
const MealCard = lazy(
  () =>
    import(
      /* webpackChunkName: "PaymentMethods__MealCard" */ 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/MealCard'
    ),
);
const PaymentMethods = lazy(
  () => import(/* webpackChunkName: "PaymentMethods" */ 'merchant/views/Settings/PaymentMethods'),
);

type Props = RouteComponentProps &
  Omit<InstrumentRequestsReducerState, 'pg'> & {
    setInstrument: (arg0: InstrumentListItem) => void;
    instruments: InstrumentsList;
    clearLeafInstrument: () => void;
    clearIntermediateInstrument: () => void;
    fetchMerchantInstruments: () => Promise<any>;
    fetchRequestedInstruments: () => Promise<any>;
    getDiscrepanciesCategories: () => Promise<any>;
    showNotification: (arg0: { type: 'error' | 'warn'; message: string }) => void;
    user: User;
    isFullScreenView?: boolean;
    routePrefix?: string;
  };

const PaymentMethodsV2 = ({
  setInstrument,
  instruments,
  clearLeafInstrument,
  clearIntermediateInstrument,
  location = {},
  leafInstrument,
  intermediateInstrument,
  loading,
  fetchMerchantInstruments,
  fetchRequestedInstruments,
  getDiscrepanciesCategories,
  showNotification,
  user,
  history,
  isFullScreenView = false,
  routePrefix = '',
}: Props): JSX.Element => {
  const handleSetInstrument = (instrument: InstrumentListItem) => {
    clearLeafInstrument();
    clearIntermediateInstrument();
    analyticsTrackWithUserInfo({
      objectName: 'method',
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        // location: 'Payment Methods',
        method: instrument.name,
      },
    });
    setInstrument(instrument);
  };

  const fetchAllInstruments = async () => {
    await Promise.all([
      fetchMerchantInstruments(),
      fetchRequestedInstruments(),
      getDiscrepanciesCategories(),
    ]).catch((errors) => {
      showNotification({
        type: 'error',
        message: errors[0] || 'Something went wrong',
      });
    });
  };

  const isIERevamp = user?.isIERevampEnabled;

  /**
   * Filter instrument based on 'additionalCondition'
   * By default, return true for all other intrument method
   */
  const filteredInstruments = useMemo(() => {
    return instruments.filter((instrument) => instrument?.additionalCondition?.(user) ?? true);
  }, [instruments]);

  useEffect(() => {
    const query = qs.parse(window.location.search);
    const instrumentAsQueryParam = query?.instrument;
    if (isIERevamp) {
      // this is for handling older query param links
      if (
        instrumentAsQueryParam &&
        PaymentMethodsTabsRoutesConfig[instrumentAsQueryParam as string]
      ) {
        history?.replace({
          pathname: PaymentMethodsTabsRoutesConfig[instrumentAsQueryParam as string],
        });
      } else if (!!instruments.length) {
        // sets instrument info on mount if payment method is opened directly from URL
        const currentRouteSlug = Object.keys(PaymentMethodsTabsRoutesConfig).find((slug) => {
          const route = PaymentMethodsTabsRoutesConfig[slug];
          return `${routePrefix}${route}` === location.pathname;
        });
        if (currentRouteSlug) {
          const currentInstrument = instruments.find(
            (instrument) => instrument.slug === currentRouteSlug,
          );
          if (currentInstrument && currentInstrument !== leafInstrument)
            setInstrument(currentInstrument);
        } else {
          // set first instrument as default payment method if path doesn't exist
          history?.replace({
            pathname: PaymentMethodsTabsRoutesConfig[instruments[0].slug],
          });
        }
      }
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [leafInstrument, intermediateInstrument, location.pathname, instruments, isIERevamp]);

  useEffect(() => {
    // loading - true instruments list is not retrieved
    /* istanbul ignore else */
    if (isIERevamp && loading) {
      fetchAllInstruments();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [loading, isIERevamp]);

  const getRefRoute = (routePath: string) => {
    return `${routePath.replace('/payment-methods/', '')}/*`;
  };

  return (
    <StyledTabContainer>
      <div className="banner-container">
        <DashboardBanner />
      </div>
      <div className="tabbed-container">
        {isFullScreenView ? null : (
          <Breadcrumb
            items={[
              accountAndSettingsLink,
              {
                label: isIERevamp ? ROUTE_MAP[location.pathname] : 'Payment  Methods',
                link: location.pathname,
              },
            ]}
          />
        )}
        <StyledHeader className="scrollable-tab-header" showBorderTop={isFullScreenView}>
          {isIERevamp ? (
            filteredInstruments.map((instrument) => {
              return (
                <NavLink
                  to={`${routePrefix}${PaymentMethodsTabsRoutesConfig[instrument.slug]}`}
                  onClick={() => handleSetInstrument(instrument)}
                  key={instrument.slug}
                >
                  {instrument.name}
                </NavLink>
              );
            })
          ) : (
            <NavLink to={`${routePrefix}${ROUTES_INFO.PAYMENT_METHODS}`}>Payment Methods</NavLink>
          )}
        </StyledHeader>
        <TestModeBanner />
        <ErrorBoundary resetOnProps>
          <StyledDivider>
            {isIERevamp ? (
              <Suspense fallback={<SectionShimmer />}>
                <Routes>
                  <Route
                    path={getRefRoute(ROUTES_INFO.CARDS)}
                    element={
                      <RouteGuard>
                        <Cards />
                      </RouteGuard>
                    }
                  />
                  <Route
                    path={getRefRoute(ROUTES_INFO.UPI_QR)}
                    element={
                      <RouteGuard>
                        <UpiQR />
                      </RouteGuard>
                    }
                  />
                  <Route
                    path={getRefRoute(ROUTES_INFO.NETBANKING)}
                    element={
                      <RouteGuard>
                        <Netbanking />
                      </RouteGuard>
                    }
                  />
                  <Route
                    path={getRefRoute(ROUTES_INFO.EMI)}
                    element={
                      <RouteGuard>
                        <Emi />
                      </RouteGuard>
                    }
                  />
                  <Route
                    path={getRefRoute(ROUTES_INFO.WALLET)}
                    element={
                      <RouteGuard>
                        <Wallet />
                      </RouteGuard>
                    }
                  />
                  <Route
                    path={getRefRoute(ROUTES_INFO.PAY_LATER)}
                    element={
                      <RouteGuard>
                        <Paylater />
                      </RouteGuard>
                    }
                  />
                  <Route
                    path={getRefRoute(ROUTES_INFO.INTERNATIONAL_PAYMENTS)}
                    element={
                      <RouteGuard>
                        <International />
                      </RouteGuard>
                    }
                  />
                  <Route
                    path={getRefRoute(ROUTES_INFO.MEAL_CARD)}
                    element={
                      <RouteGuard>
                        <MealCard />
                      </RouteGuard>
                    }
                  />
                </Routes>
              </Suspense>
            ) : (
              <Suspense fallback={<Loader />}>
                <div className="content">
                  <Routes>
                    <Route
                      index
                      element={
                        <RouteGuard>
                          <PaymentMethods />
                        </RouteGuard>
                      }
                    />
                  </Routes>
                </div>
              </Suspense>
            )}
          </StyledDivider>
        </ErrorBoundary>
      </div>
    </StyledTabContainer>
  );
};

const mapStateToProps = (state: Store) => {
  return {
    instruments: state.instrumentRequests.pg,
    leafInstrument: state.instrumentRequests.leafInstrument,
    intermediateInstrument: state.instrumentRequests.intermediateInstrument,
    loading: state.instrumentRequests.loading,
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      setInstrument,
      clearIntermediateInstrument,
      clearLeafInstrument,
      fetchMerchantInstruments,
      fetchRequestedInstruments,
      getDiscrepanciesCategories,
      showNotification,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(withRouter(PaymentMethodsV2));
