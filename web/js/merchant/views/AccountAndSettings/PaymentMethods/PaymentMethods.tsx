import React, { Suspense, useEffect, useMemo } from 'react';
import { Route, NavLink, RouteComponentProps } from 'react-router-dom';
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
  };

const PaymentMethodsV2 = ({
  setInstrument,
  instruments,
  clearLeafInstrument,
  clearIntermediateInstrument,
  location,
  leafInstrument,
  intermediateInstrument,
  loading,
  fetchMerchantInstruments,
  fetchRequestedInstruments,
  getDiscrepanciesCategories,
  showNotification,
  user,
  history,
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
        history.replace({
          pathname: PaymentMethodsTabsRoutesConfig[instrumentAsQueryParam as string],
        });
      } else if (!!instruments.length) {
        // sets instrument info on mount if payment method is opened directly from URL
        const currentRouteSlug = Object.keys(PaymentMethodsTabsRoutesConfig).find((slug) => {
          const route = PaymentMethodsTabsRoutesConfig[slug];
          return route === location.pathname;
        });
        if (currentRouteSlug) {
          const currentInstrument = instruments.find(
            (instrument) => instrument.slug === currentRouteSlug,
          );
          if (currentInstrument && currentInstrument !== leafInstrument)
            setInstrument(currentInstrument);
        } else {
          // set first instrument as default payment method if path doesn't exist
          history.replace({
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

  return (
    <StyledTabContainer>
      <div className="banner-container">
        <DashboardBanner />
      </div>
      <div className="tabbed-container">
        <Breadcrumb
          items={[
            accountAndSettingsLink,
            {
              label: isIERevamp ? ROUTE_MAP[location.pathname] : 'Payment  Methods',
              link: location.pathname,
            },
          ]}
        />
        <StyledHeader className="scrollable-tab-header">
          {isIERevamp ? (
            filteredInstruments.map((instrument) => {
              return (
                <NavLink
                  to={PaymentMethodsTabsRoutesConfig[instrument.slug]}
                  onClick={() => handleSetInstrument(instrument)}
                  key={instrument.slug}
                >
                  {instrument.name}
                </NavLink>
              );
            })
          ) : (
            <NavLink to={ROUTES_INFO.PAYMENT_METHODS}>Payment Methods</NavLink>
          )}
        </StyledHeader>
        <TestModeBanner />
        <ErrorBoundary resetOnProps>
          <StyledDivider>
            {isIERevamp ? (
              <Suspense fallback={<SectionShimmer />}>
                <Route path={ROUTES_INFO.CARDS} component={Cards} />
                <Route path={ROUTES_INFO.UPI_QR} component={UpiQR} />
                <Route path={ROUTES_INFO.NETBANKING} component={Netbanking} />
                <Route path={ROUTES_INFO.EMI} component={Emi} />
                <Route path={ROUTES_INFO.WALLET} component={Wallet} />
                <Route path={ROUTES_INFO.PAY_LATER} component={Paylater} />
                <Route path={ROUTES_INFO.INTERNATIONAL_PAYMENTS} component={International} />
                <Route path={ROUTES_INFO.MEAL_CARD} component={MealCard} />
              </Suspense>
            ) : (
              <Suspense fallback={<Loader />}>
                <div className="content">
                  <Route path={ROUTES_INFO.PAYMENT_METHODS} component={PaymentMethods} />
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

export default connect(mapStateToProps, mapDispatchToProps)(PaymentMethodsV2);
