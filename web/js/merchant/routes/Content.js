/* eslint-disable import/order */
/* eslint-disable react/no-unsafe */
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { ModalMask } from 'common/new-ui/Modal';
import { withSplitzService } from 'common/splitz';
import Loader from 'common/ui/Loader';
import MultiSlider from 'common/ui/MultiSlider';
import { getXCAStatus } from 'common/ui/NotificationsDropdown/Neostone/common/utils';
import Slider from 'common/ui/Slider';
import { analyticsTrack } from 'common/utils/analytics';
import {
  classList,
  getCommonAnalyticsProperties,
  isMobileResolution,
} from 'common/utils/rzp-utils';
import { isMobileDevice } from 'merchant/components/Home/data';
import Home from 'merchant/containers/Home/Index';
import { setActiveEntity, setBaseLocation, setSecActiveEntity } from 'merchant/reducers/app';
import { matchDetail, matchModal, supportHashMapping } from 'merchant/routes';
import { openSlider } from 'merchant_common/reducers/slider';
import qs from 'query-string';
import React, { Component, Suspense } from 'react';
import { connect } from 'react-redux';
import { NavLink, Navigate, Route, Routes, matchPath } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import RepaymentsSchedule from 'merchant/views/Capital/CashAdvance/RepaymentsSchedule';
import HandleIndex from './HandleIndex';
import lazy from './LazyLoader';
import { getIsPayrollWidgetEnabled } from 'merchant/components/Sidebar/helpers';
import {
  isTrustedBadgeAllowed,
  isPaymentMethodEnabled,
  isProfileViewAllowed,
  isConfigurationViewAllowed,
} from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import { canViewCashAdvanceProduct, canViewLOCEMIProduct } from 'merchant/views/Capital/utils';
import { RouteGuard } from 'merchant/components/ShowWhen';

// import { isPosExperimentEnabled } from 'merchant/views/POS/helpers';
import { isTransactionsV2Enabled } from 'merchant/views/Transactions/v2/common/utils';
import { withI18Service } from 'common/i18';

const B2bPaymentsList = lazy(() =>
  import(
    /* webpackChunkName: "B2bPaymentsList" */ 'merchant/views/Transactions/v1/B2bPayments/List'
  ),
);

const BatchPaymentsList = lazy(() =>
  import(
    /* webpackChunkName: "BatchPaymentsList" */ 'merchant/views/Transactions/v1/BatchPayments/List'
  ),
);

const BatchRefundsUpload = lazy(() =>
  import(
    /* webpackChunkName: "BatchRefundsUpload" */ 'merchant/views/Transactions/v1/BatchRefunds/BatchUpload'
  ),
);

const BatchRefundsList = lazy(() =>
  import(
    /* webpackChunkName: "BatchRefundsList" */ 'merchant/views/Transactions/v1/BatchRefunds/List'
  ),
);

const DisputesList = lazy(() =>
  import(/* webpackChunkName: "DisputesList" */ 'merchant/views/Transactions/v1/Disputes/List'),
);

const OrdersList = lazy(() =>
  import(/* webpackChunkName: "OrdersList" */ 'merchant/views/Transactions/v1/Orders/List'),
);

const PaymentsList = lazy(() =>
  import(/* webpackChunkName: "PaymentsList" */ 'merchant/views/Transactions/v1/Payments/List'),
);

const RefundsList = lazy(() =>
  import(/* webpackChunkName: "RefundsList" */ 'merchant/views/Transactions/v1/Refunds/List'),
);

const SuccessRate = lazy(() =>
  import(/* webpackChunkName: "SuccessRate" */ 'merchant/views/Transactions/v1/SuccessRate'),
);

const UploadInvoice = lazy(() =>
  import(/* webpackChunkName: "UploadInvoice" */ 'merchant/views/Transactions/v1/UploadInvoice'),
);

const Invoices = lazy(() =>
  import(/* webpackChunkName: "Invoices" */ 'merchant/views/Invoices/Invoices/List'),
);

const Items = lazy(() =>
  import(/* webpackChunkName: "Items" */ 'merchant/views/Invoices/Items/List'),
);

const PaymentButtonList = lazy(() =>
  import(
    /* webpackChunkName: "PaymentButtonList" */ 'merchant/views/PaymentButton/PaymentButton/List'
  ),
);

const SubscriptionButtonList = lazy(() =>
  import(
    /* webpackChunkName: "SubscriptionButtonList" */ 'merchant/views/PaymentButton/SubscriptionButton/List'
  ),
);

const SubscriptionsList = lazy(() =>
  import(
    /* webpackChunkName: "SubscriptionsList" */ 'merchant/views/Subscriptions/Subscriptions/List'
  ),
);

const PlansList = lazy(() =>
  import(/* webpackChunkName: "PlansList" */ 'merchant/views/Subscriptions/Plans/List'),
);

const SubscriptionSettings = lazy(() =>
  import(/* webpackChunkName: "SubscriptionSettings" */ 'merchant/views/Subscriptions/Settings'),
);

const TokensList = lazy(() =>
  import(/* webpackChunkName: "TokensList" */ 'merchant/views/Subscriptions/Tokens/List'),
);

const RegistrationLinksList = lazy(() =>
  import(
    /* webpackChunkName: "RegistrationLinksList" */ 'merchant/views/Subscriptions/RegistrationLinks/List'
  ),
);

const HostedEmanadateBatches = lazy(() =>
  import(
    /* webpackChunkName: "HostedEmanadateBatches" */ 'merchant/views/Subscriptions/Batch/List'
  ),
);

const RecurringPayments = lazy(() =>
  import(
    /* webpackChunkName: "RecurringPayments" */ 'merchant/views/Subscriptions/RecurringPayments/List'
  ),
);

const VirtualAccountsList = lazy(() =>
  import(
    /* webpackChunkName: "VirtualAccountsList" */ 'merchant/views/SmartCollect/VirtualAccounts/List'
  ),
);

const SmartCollectPaymentsList = lazy(() =>
  import(
    /* webpackChunkName: "SmartCollectPaymentsList" */ 'merchant/views/SmartCollect/Payments/List'
  ),
);

const BatchExpiryUpdate = lazy(() =>
  import(
    /* webpackChunkName: "BatchExpiryUpdate" */ 'merchant/views/SmartCollect/BatchExpiryUpdate/List'
  ),
);

const TrustedBadge = lazy(() =>
  import(/* webpackChunkName: "TrustedBadge" */ 'merchant/views/Account/TrustedBadge'),
);

const Profile = lazy(() =>
  import(/* webpackChunkName: "Profile" */ 'merchant/views/Account/Profile'),
);

const WebsiteAppDetails = lazy(() =>
  import(/* webpackChunkName: "WebsiteAppDetails" */ 'merchant/views/Account/WebsiteAppDetails'),
);

const Balances = lazy(() =>
  import(/* webpackChunkName: "Balances" */ 'merchant/views/Account/Balances'),
);

const Credits = lazy(() =>
  import(/* webpackChunkName: "Credits" */ 'merchant/views/Account/Credits/List'),
);

const ManageTeam = lazy(() =>
  import(/* webpackChunkName: "ManageTeam" */ 'merchant/views/Account/ManageTeam'),
);

const PricingPlans = lazy(() =>
  import(
    /* webpackChunkName: "PricingPlans" */ 'merchant/views/AccountAndSettings/Pricing/components/PricingPlans'
  ),
);

const Referrals = lazy(() =>
  import(/* webpackChunkName: "Referrals" */ 'merchant/views/Account/Referrals/List'),
);

const Conversations = lazy(() =>
  import(
    /* webpackChunkName: "Conversations" */ 'merchant/views/TicketSupport/components/Conversations'
  ),
);

const TicketsContainer = lazy(() =>
  import(
    /* webpackChunkName: "TicketsContainer" */ 'merchant/views/TicketSupport/components/TicketsContainer'
  ),
);

const Tickets = lazy(() =>
  import(/* webpackChunkName: "Tickets" */ 'merchant/views/TicketSupport/components/Tickets'),
);

const TransactionV2Landing = lazy(() =>
  import(/* webpackChunkName: "TransactionV2Landing" */ 'merchant/views/Transactions/v2/Landing'),
);

const RouteOndemandSettlements = lazy(() =>
  import(
    /* webpackChunkName: "RouteOndemandSettlements" */ 'merchant/views/Settlements/RouteOndemandSettlements'
  ),
);

const ItemsComponent = (props) => <Items {...props} isInvoiceView />;

const ApiKeysAndPlugins = lazy(() =>
  import(/* webpackChunkName: "ApiKeysAndPlugins" */ 'merchant/views/ApiKeysAndPlugins'),
);

const AccountAndSettingsHome = lazy(() =>
  import(
    /* webpackChunkName: "AccountAndSettingsHome" */ 'merchant/views/AccountAndSettings/AccountAndSettingsHome'
  ),
);

const PartnerDashboard = lazy(() =>
  import(/* webpackChunkName: "PartnerDashboard" */ 'merchant/views/PartnerDashboard'),
);
const Transactions = lazy(() =>
  import(/* webpackChunkName: "Transactions" */ 'merchant/views/Transactions/v1'),
);
const Settlements = lazy(() =>
  import(/* webpackChunkName: "Settlements" */ 'merchant/views/Settlements'),
);
const InstantSettlementPayoutDetails = lazy(() =>
  import(
    /* webpackChunkName: "InstantSettlementPayoutDetails" */ 'merchant/views/Settlements/InstantSettlements/PayoutDetails'
  ),
);
const SettlementDetailsV2 = lazy(() =>
  import(/* webpackChunkName: "SettlementDetails" */ 'merchant/views/Settlements/v2/Details'),
);
const SettlementDetailsV3 = lazy(() =>
  import(/* webpackChunkName: "SettlementDetails" */ 'merchant/views/Settlements/v3'),
);
const PaymentLinks = lazy(() =>
  import(/* webpackChunkName: "PaymentLinks" */ 'merchant/views/PaymentLinks/Index'),
);
const PaymentPages = lazy(() =>
  import(/* webpackChunkName: "PaymentPages" */ 'merchant/views/PaymentPages'),
);
const PaymentPagesDetails = lazy(() =>
  import(/* webpackChunkName: "PaymentPages" */ 'merchant/views/PaymentPages/PaymentPages/Details'),
);
const BatchUploadContainer = lazy(() =>
  import(/* webpackChunkName: "PaymentPages" */ 'merchant/views/PaymentPages/BatchUpload'),
);
const InvoicesContainer = lazy(() =>
  import(/* webpackChunkName: "Invoices" */ 'merchant/views/Invoices'),
);
const InvoicesNew = lazy(() =>
  import(/* webpackChunkName: "Invoices" */ 'merchant/views/Invoices/Invoices/New'),
);
const Subscriptions = lazy(() =>
  import(/* webpackChunkName: "Subscriptions" */ 'merchant/views/Subscriptions'),
);

const Affordability = lazy(() =>
  import(/* webpackChunkName: "Affordability" */ 'merchant/views/Affordability'),
);

const PaymentMetrics = lazy(() =>
  import(/* webpackChunkName: "PaymentMetrics" */ 'merchant/views/PaymentMetrics'),
);

const QRCodes = lazy(() => import(/* webpackChunkName: "QRCodes" */ 'merchant/views/QRCodes'));

const Stores = lazy(() => import(/* webpackChunkName: "Stores" */ 'merchant/views/Stores'));

const Customers = lazy(() =>
  import(/* webpackChunkName: "Customers" */ 'merchant/views/Customers/List'),
);
const Marketplace = lazy(() =>
  import(/* webpackChunkName: "Marketplace" */ 'merchant/views/Marketplace/Index'),
);

const ConnectedBanking = lazy(() =>
  import(/* webpackChunkName: "ConnectedBanking" */ 'merchant/views/ConnectedBanking'),
);

const RazorpayXWidget = lazy(() =>
  import(
    /* webpackChunkName: "RazorpayXWidget" */ 'merchant/views/RazorpayXWidget/RazorpayXWidget'
  ),
);

const BbpsComponent = lazy(() => import(/* webpackChunkName: "BBPS" */ 'merchant/views/BBPS'));
const PaymentButton = lazy(() =>
  import(/* webpackChunkName: "PaymentButton" */ 'merchant/views/PaymentButton'),
);
const PaymentButtonsDetails = lazy(() =>
  import(
    /* webpackChunkName: "PaymentButton" */ 'merchant/views/PaymentButton/PaymentButton/Details'
  ),
);
const SubscriptionButtonDetails = lazy(() =>
  import(
    /* webpackChunkName: "SubscriptionButton" */ 'merchant/views/PaymentButton/SubscriptionButton/Details'
  ),
);

const MerchantReports = lazy(() =>
  import(
    /* webpackChunkName: "MerchantReports" */ 'merchant_common/views/Reports/views/MerchantReports'
  ),
);

const MyAccount = lazy(() => import(/* webpackChunkName: "Account" */ 'merchant/views/Account'));

const Settings = lazy(() => import(/* webpackChunkName: "Settings" */ 'merchant/views/Settings'));

const WebsiteAndAppSettings = lazy(() =>
  import(
    /* webpackChunkName: "WebsiteAndAppSettings" */ 'merchant/views/AccountAndSettings/WebsiteAppSettings'
  ),
);

const BusinessSettings = lazy(() =>
  import(
    /* webpackChunkName: "BusinessSettings" */ 'merchant/views/AccountAndSettings/BusinessSettings'
  ),
);

const CheckoutSettings = lazy(() =>
  import(
    /* webpackChunkName: "CheckoutSettings" */ 'merchant/views/AccountAndSettings/CheckoutSettings'
  ),
);

const PaymentsAndRefundsSettings = lazy(() =>
  import(
    /* webpackChunkName: "PaymentsAndRefundsSettings" */ 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings'
  ),
);

const Pricing = lazy(() =>
  import(/* webpackChunkName: "Pricing" */ 'merchant/views/AccountAndSettings/Pricing'),
);

const BankAccountsAndSettlements = lazy(() =>
  import(
    /* webpackChunkName: "BankAccountsAndSettlements" */ 'merchant/views/AccountAndSettings/BankAccountsAndSettlements'
  ),
);

// const InternationalSettings = lazy(() =>
//   import(
//     /* webpackChunkName: "InternationalSettings" */ 'merchant/views/AccountAndSettings/InternationalSettings'
//   ),
// );

const NotificationSettings = lazy(() =>
  import(
    /* webpackChunkName: "NotificationSettings" */ 'merchant/views/AccountAndSettings/NotificationSettings'
  ),
);

const PaymentMethodsV2 = lazy(() =>
  import(
    /* webpackChunkName: "PaymentMethods" */ 'merchant/views/AccountAndSettings/PaymentMethods'
  ),
);

const SmartCollect = lazy(() =>
  import(/* webpackChunkName: "SmartCollect" */ 'merchant/views/SmartCollect/Index'),
);

const Navigator = lazy(() =>
  import(/* webpackChunkName: "Navigator" */ 'merchant/views/Navigator/Index'),
);

const Offers = lazy(() => import(/* webpackChunkName: "Offers" */ 'merchant/views/Offers'));

const CheckoutRewards = lazy(() =>
  import(/* webpackChunkName: "CheckoutRewards" */ 'merchant/views/CheckoutRewards'),
);

const PaypalOnboardRedirect = lazy(() =>
  import(
    /* webpackChunkName: "PaypalOnboardRedirect" */ 'merchant/views/Settings/Configuration/PaypalOnboardRedirect'
  ),
);

const LoanDetails = lazy(() =>
  import(/* webpackChunkName: "CapitalLoans" */ 'merchant/views/Capital/Loans'),
);

const NonFldgLoans = lazy(() =>
  import(/* webpackChunkName: "NonFldgLoans" */ 'merchant/views/Capital/NonFldgLoans/NonFldgLoans'),
);

const FlashCreditLandingPage = lazy(() =>
  import(/* webpackChunkName: "CapitalCashAdvance" */ 'merchant/views/Capital/CashAdvance/index'),
);

const CashAdvance = lazy(() =>
  import(/* webpackChunkName: "CashAdvance" */ 'merchant/views/Capital/CashAdvance/CashAdvance'),
);

const CorporateCards = lazy(() =>
  import(
    /* webpackChunkName: "CorporateCards" */ 'merchant/views/Capital/CorporateCards/CorporateCards'
  ),
);

const LoansCollections = lazy(() =>
  import(/* webpackChunkName: "Loans" */ 'merchant/views/Capital/Loans/LoansCollections'),
);

const MagicCheckout = lazy(() =>
  import(/* webpackChunkName: "MagicCheckout" */ 'merchant/views/MagicCheckout'),
);

const Developers = lazy(() =>
  import(/* webpackChunkName: "Developers" */ 'merchant/views/Developers'),
);
const DevelopersWebhooks = lazy(() =>
  import(/* webpackChunkName: "DevelopersWebhooks" */ 'merchant/views/Developers/Webhooks'),
);

const HelpSection = lazy(() =>
  import(/* webpackChunkName: "new-help-section" */ 'merchant/components/Support/HelpSection'),
);

const PayrollWidget = lazy(() =>
  import(/* webpackChunkName: "PayrollWidget" */ 'merchant/views/Payroll'),
);

const PaymentHandle = lazy(() =>
  import(/* webpackChunkName: "PaymentHandle" */ 'merchant/views/PaymentHandle'),
);
const Wallet = lazy(() => import(/* webpackChunkName: "IssuingWallet" */ 'merchant/views/Wallet'));

// const POS = lazy(() => import(/* webpackChunkName: "POS" */ 'merchant/views/POS'));
const PaymentsDetailsV2 = lazy(() =>
  import(
    /* webpackChunkName: "PaymentsDetailsV2" */ 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails'
  ),
);

const PaymentsContainer = lazy(() =>
  import(
    /* webpackChunkName: "PaymentsContainer" */ 'merchant/views/Transactions/v2/Payments/components/PaymentsContainer'
  ),
);

const TransactionsV2EntitiesOverview = lazy(() =>
  import(
    /* webpackChunkName: "PaymentsContainer" */ 'merchant/views/Transactions/v2/EntitiesOverview'
  ),
);

const TransactionV2RefundsContainer = lazy(() =>
  import(
    /* webpackChunkName: "RefundsContainer" */ 'merchant/views/Transactions/v2/Refunds/components/RefundsContainer'
  ),
);

@withI18Service
@connect(
  (state) => ({
    user: state.session.user,
    mode: state.session.mode,
    isMobile: state.app.isMobileResolution,
  }),
  {
    setBaseLocation,
    setActiveEntity,
    setSecActiveEntity,
    openSlider,
  },
)
class Content extends Component {
  checkIsTransactionsV2Enabled = () => {
    const { splitz, user } = this.props;
    return isTransactionsV2Enabled(splitz, user);
  };

  setBaseLocation = (location) => {
    const blacklistedDetailsRoutes = ['/payments/:id', '/refunds/:id'];
    let matchDetailsRoute;
    const extraConfig = {
      i18: this.props.i18,
      splitz: {
        abExperiments: this.props.splitz.abExperiments,
      },
    };
    if (
      this.checkIsTransactionsV2Enabled() &&
      blacklistedDetailsRoutes.some((route) =>
        matchPath({ path: route, exact: true }, location.pathname),
      )
    ) {
      matchDetailsRoute = null;
    } else {
      matchDetailsRoute = matchDetail(location.pathname, extraConfig);
    }
    const matchModalsRoute = matchModal(location.pathname, extraConfig);

    if (matchDetailsRoute || matchModalsRoute) {
      let resultRoute;

      if (matchModalsRoute && matchModalsRoute.match) {
        resultRoute = matchModalsRoute;

        this.modalView = matchModalsRoute.component;
        this.detailView = null;
      } else if (matchDetailsRoute && matchDetailsRoute.match) {
        resultRoute = matchDetailsRoute;

        this.modalView = null;
        this.detailView = matchDetailsRoute.component;
      }

      const params = resultRoute.match.params;
      this.props.setActiveEntity(params.id);

      this.detailProps = params;

      this.props.setActiveEntity(resultRoute.match.params.id);
      if (Object.keys(params > 1)) {
        this.props.setSecActiveEntity(params[Object.keys(params)[1]]);
      }

      const query = qs.parse(location.search);

      if (query.basePath) {
        const _location = {
          ...location,
          pathname: query.basePath,
        };
        this.baseLocation = _location;
        this.props.setBaseLocation(_location);
      }
    } else {
      this.detailView = null;
      this.modalView = null;
      this.detailProps = null;

      this.props.setActiveEntity(null);
      this.props.setSecActiveEntity(null);

      this.baseLocation = location;
      this.props.setBaseLocation(location);
    }
  };

  toggleRasieTicketModal = ({ location = {} }) => {
    /*eslint-disable-next-line func-names */
    const onModalClose = function () {
      this.props.history.push(location.pathname);
      window.rzpTicketSystem.removeEventListener('modal-close', onModalClose);
    }.bind(this); //so that this.props is available inside onModalClose

    // For handling where url is encoded, so hash becomes part of pathname instead of hash (In gmail redirection).
    const urlWithHash = decodeURIComponent(location.pathname);
    const hashInUrl = urlWithHash.substring(urlWithHash.indexOf('#') + 1);

    const hash = `${location.hash || `#${hashInUrl}`}`;

    if (window.rzpTicketSystem) {
      const actionHash = supportHashMapping[hash];
      if (actionHash && !!location.pathname && location.pathname !== '/') {
        if (window.rzpTicketSystem.addEventListener) {
          window.rzpTicketSystem.addEventListener('modal-close', onModalClose);
        }
        window.rzpTicketSystem.openModal(actionHash, {
          chat: false,
          call: false,
        });
      } else if (window?.rzpTicketSystem?.$el?.classList?.contains('open')) {
        window.rzpTicketSystem.closeModal();
      }
    }
  };

  listenTrackEvents = () => {
    if (window.rzpTicketSystem && window.rzpTicketSystem.addEventListener) {
      window.rzpTicketSystem.addEventListener('track-event', function trackEvent(data) {
        return analyticsTrack({
          ...data.event,
          objectName: data.event.objectName,
          actionName: data.event.actionName,
          properties: {
            ...data.properties,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      });
    }
  };

  getBaseView = () => {
    const {
      fullPageView,
      user,
      mode,
      i18: { isConfigTagEnabled },
      splitz,
    } = this.props;
    const { abExperiments } = splitz;
    const extraConfig = { isConfigTagEnabled, abExperiments };

    if (fullPageView) return fullPageView;

    const PaymentMethods = user.isAccountAndSettingsRevampEnabled ? PaymentMethodsV2 : Settings;
    const isTransactionV2Enabled = this.checkIsTransactionsV2Enabled();
    return (
      <Suspense fallback={<Loader />}>
        <Routes location={this.baseLocation}>
          <Route path="*" element={<HandleIndex />} />
          <Route path="dashboard/*" element={<Home />} />

          <Route
            path="partners/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isPartner() && user.isAllowedView('partner_navlinks')
                }
              >
                <PartnerDashboard />
              </RouteGuard>
            }
          />

          <Route
            path="account-settings/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isAccountAndSettingsRevampEnabled &&
                  user.isAllowedMultiple(
                    'webhooks applications configuration api_keys profile credits add_funds team referrals',
                  )
                }
              >
                <AccountAndSettingsHome />
              </RouteGuard>
            }
          />

          <Route
            path="failed-payments/*"
            element={
              <RouteGuard additionalCondition={(user) => user.isAllowedView('payments')}>
                <TransactionsV2EntitiesOverview />
              </RouteGuard>
            }
          >
            <Route
              index
              element={
                <RouteGuard>
                  <PaymentsContainer />
                </RouteGuard>
              }
            />
          </Route>

          <Route path="payments/*">
            <Route
              path="*"
              element={
                <RouteGuard additionalCondition={(user) => user.isAllowedView('payments')}>
                  {isTransactionV2Enabled ? <TransactionV2Landing /> : <Transactions />}
                </RouteGuard>
              }
            >
              <Route
                index
                element={
                  <RouteGuard>
                    {isTransactionV2Enabled ? <PaymentsContainer /> : <PaymentsList />}
                  </RouteGuard>
                }
              />

              <Route path="batchuploads/*">
                <Route
                  index
                  element={
                    <RouteGuard>
                      <BatchPaymentsList />
                    </RouteGuard>
                  }
                />
                <Route
                  path=":mode/*"
                  element={
                    <RouteGuard>
                      <BatchPaymentsList />
                    </RouteGuard>
                  }
                />
              </Route>

              <Route
                path="invoices/*"
                element={
                  <RouteGuard additionalCondition={(usr) => usr.isAllowedView('b2b_payments')}>
                    <UploadInvoice />
                  </RouteGuard>
                }
              />

              <Route
                path="b2b-exports/*"
                element={
                  <RouteGuard additionalCondition={(usr) => usr.isAllowedView('b2b_payments')}>
                    <B2bPaymentsList />
                  </RouteGuard>
                }
              />
            </Route>
            <Route
              path=":id"
              element={
                <RouteGuard
                  additionalCondition={(user) =>
                    isTransactionV2Enabled && user.isAllowedView('payments')
                  }
                >
                  <PaymentsDetailsV2 />
                </RouteGuard>
              }
            />
          </Route>

          <Route path="refunds/*">
            <Route
              path="*"
              element={
                <RouteGuard
                  additionalCondition={(user) =>
                    user.isAllowedView('refunds') && !isConfigTagEnabled('refunds.refund')
                  }
                >
                  {isTransactionV2Enabled ? <TransactionsV2EntitiesOverview /> : <Transactions />}
                </RouteGuard>
              }
            >
              <Route
                index
                element={
                  <RouteGuard additionalCondition={() => !isConfigTagEnabled('refunds.refund')}>
                    {isTransactionV2Enabled ? <TransactionV2RefundsContainer /> : <RefundsList />}
                  </RouteGuard>
                }
              />
              <Route
                path="batchuploads/*"
                element={
                  <RouteGuard additionalCondition={() => !isConfigTagEnabled('refunds.refund')}>
                    <BatchRefundsList />
                  </RouteGuard>
                }
              />

              <Route
                path="batchupload/*"
                element={
                  <RouteGuard additionalCondition={() => !isConfigTagEnabled('refunds.refund')}>
                    <BatchRefundsUpload />
                  </RouteGuard>
                }
              />
            </Route>
            <Route
              path=":id"
              element={
                <RouteGuard
                  additionalCondition={(user) =>
                    isTransactionV2Enabled && user.isAllowedView('refunds')
                  }
                >
                  <PaymentsDetailsV2 />
                </RouteGuard>
              }
            />
          </Route>

          <Route
            path="orders/*"
            element={
              <RouteGuard additionalCondition={(user) => user.isAllowedView('orders')}>
                {isTransactionV2Enabled ? <TransactionV2Landing /> : <Transactions />}
              </RouteGuard>
            }
          >
            <Route
              index
              element={
                <RouteGuard additionalCondition={(usr) => usr.isAllowedView('orders')}>
                  <OrdersList />
                </RouteGuard>
              }
            />
          </Route>

          <Route
            path="disputes/*"
            element={
              <RouteGuard>
                {isTransactionV2Enabled ? <TransactionsV2EntitiesOverview /> : <Transactions />}
              </RouteGuard>
            }
          >
            <Route
              index
              element={
                <RouteGuard
                  additionalCondition={(usr) =>
                    usr.isAllowedView('refunds') && !isConfigTagEnabled('disputes.disputes')
                  }
                >
                  <DisputesList />
                </RouteGuard>
              }
            />
          </Route>

          <Route
            path="success-rate/*"
            element={
              <RouteGuard
                isTagDependent
                additionalCondition={(currentUser) =>
                  mode === 'live' &&
                  currentUser.isSuccessRateEnabled &&
                  currentUser.isAllowedView('success_rate')
                }
              >
                {isTransactionV2Enabled ? <TransactionsV2EntitiesOverview /> : <Transactions />}
              </RouteGuard>
            }
          >
            <Route
              index
              element={
                <RouteGuard
                  additionalCondition={(currentUser) =>
                    mode === 'live' &&
                    currentUser.isSuccessRateEnabled &&
                    currentUser.isAllowedView('success_rate')
                  }
                >
                  <SuccessRate />
                </RouteGuard>
              }
            />
          </Route>

          <Route path="settlements/*">
            <Route
              index
              element={
                <RouteGuard
                  additionalCondition={(user) =>
                    user.isAllowedView('settlements') && user.hideForNIASupportRole
                  }
                >
                  <Settlements />
                </RouteGuard>
              }
            />
            <Route
              path=":id/*"
              element={
                <RouteGuard
                  additionalCondition={(user) =>
                    user.isAllowedView('settlements') && user.hideForNIASupportRole
                  }
                >
                  {user.isSettlementV3RevampEnabled ? (
                    <SettlementDetailsV3 />
                  ) : (
                    <SettlementDetailsV2 />
                  )}
                </RouteGuard>
              }
            />
          </Route>

          <Route
            path="/routeinstantsettlements"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isAllowedView('early_settlement') &&
                  user.isOndemandRouteSettlementsEnabled &&
                  !isConfigTagEnabled('settlements.settlement')
                }
              >
                <Settlements>
                  <RouteOndemandSettlements />
                </Settlements>
              </RouteGuard>
            }
          />

          <Route
            path="/instantsettlements"
            element={
              <RouteGuard additionalCondition={(user) => user.isAllowedView('early_settlement')}>
                <Settlements />
              </RouteGuard>
            }
          />

          <Route
            path="instantsettlement_details/:id/*"
            element={
              <RouteGuard additionalCondition={(user) => user.isAllowedView('early_settlement')}>
                <InstantSettlementPayoutDetails />
              </RouteGuard>
            }
          />

          <Route path="invoices/*">
            <Route
              index
              element={
                <RouteGuard additionalCondition={(user) => user.isAllowedView('invoices')}>
                  <InvoicesContainer>
                    <Invoices />
                  </InvoicesContainer>
                </RouteGuard>
              }
            />
            <Route
              path="new"
              element={
                <RouteGuard
                  additionalCondition={(user) =>
                    user.isAllowedEdit('invoices') && !isConfigTagEnabled('invoices.invoice')
                  }
                >
                  <InvoicesNew />
                </RouteGuard>
              }
            />
            <Route
              path=":id/*"
              element={
                <RouteGuard
                  additionalCondition={(user) =>
                    user.isAllowedView('invoices') && !isConfigTagEnabled('invoices.invoice')
                  }
                >
                  <InvoicesNew />
                </RouteGuard>
              }
            />
          </Route>

          <Route
            path="items/*"
            element={
              <RouteGuard additionalCondition={(user) => user.isAllowedView('invoices')}>
                <InvoicesContainer>
                  <ItemsComponent />
                </InvoicesContainer>
              </RouteGuard>
            }
          />

          <Route
            path="paymentlinks/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isAllowedView('payment_links') &&
                  !isConfigTagEnabled('payment_links.payment_link')
                }
              >
                <PaymentLinks />
              </RouteGuard>
            }
          />

          <Route
            path="payment-handle/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isAllowedView('payment_handle') &&
                  user.isPaymentHandleSplitzEnabled &&
                  !isConfigTagEnabled('payments.payment_handle')
                }
              >
                <PaymentHandle />
              </RouteGuard>
            }
          />

          <Route
            path="paymenthandle/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isAllowedView('payment_handle') &&
                  !isConfigTagEnabled('payments.payment_handle')
                }
              >
                <PaymentHandle />
              </RouteGuard>
            }
          />

          <Route path="paymentpages/*">
            <Route
              path="*"
              element={
                <RouteGuard
                  additionalCondition={(user) =>
                    user.isAllowedView('payment_pages') &&
                    !isConfigTagEnabled('payment_pages.payment_pages')
                  }
                >
                  <PaymentPages />
                </RouteGuard>
              }
            />

            <Route
              path={`batchpaymentpages/:id/:entity_name/*`}
              element={
                <RouteGuard additionalCondition={(user) => user.isPaymentPageFileUploadEnabled}>
                  <PaymentPagesDetails isBatchPaymentPages />
                </RouteGuard>
              }
            />

            <Route
              path="storefront/:id/:entity_name/*"
              element={
                <RouteGuard
                  additionalCondition={(user) =>
                    user.isAllowedView('payment_pages') &&
                    !isConfigTagEnabled('payment_pages.payment_pages') &&
                    user.isPaymentPageStorefrontEnabled
                  }
                >
                  <PaymentPagesDetails isStorefrontPage />
                </RouteGuard>
              }
            />

            <Route
              path=":id/:entity_name/*"
              element={
                <RouteGuard
                  additionalCondition={(user) =>
                    user.isAllowedView('payment_pages') &&
                    !isConfigTagEnabled('payment_pages.payment_pages')
                  }
                >
                  <PaymentPagesDetails />
                </RouteGuard>
              }
            />

            <Route
              path="batchuploads/:id/:title/*"
              element={
                <RouteGuard additionalCondition={(user) => user.isPaymentPageFileUploadEnabled}>
                  <BatchUploadContainer />
                </RouteGuard>
              }
            />
          </Route>

          <Route
            path="wallet/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isIssuingDashboardEnabled ||
                  user.isIssuingBulkUploadEnabled ||
                  user.isIssuingFundsTabEnabled
                }
              >
                <Wallet />
              </RouteGuard>
            }
          />

          <Route
            path="magic/*"
            element={
              <RouteGuard additionalCondition={(user) => user.isMagicCheckoutEnabled}>
                <MagicCheckout />
              </RouteGuard>
            }
          />
          <Route path="super-checkout/*" element={<Navigate to="/magic" replace />} />

          <Route path="paymentbuttons/*">
            <Route
              index
              element={
                <RouteGuard
                  additionalCondition={(user) =>
                    user.isAllowedView('payment_buttons') &&
                    user.isPaymentButtonEnabledByRazorX &&
                    !isConfigTagEnabled('payment_buttons.payment_buttons')
                  }
                >
                  <PaymentButton>
                    <PaymentButtonList />
                  </PaymentButton>
                </RouteGuard>
              }
            />

            <Route
              path=":id/:entity_name/*"
              element={
                <RouteGuard
                  additionalCondition={(user) =>
                    user.isAllowedView('payment_buttons') &&
                    user.isPaymentButtonEnabledByRazorX &&
                    !isConfigTagEnabled('payment_buttons.payment_buttons')
                  }
                >
                  <PaymentButtonsDetails />
                </RouteGuard>
              }
            />
          </Route>

          <Route path="subscription_buttons/*">
            <Route
              index
              element={
                <RouteGuard
                  additionalCondition={(user) =>
                    user.isAllowedView('subscription_buttons') &&
                    user.isSubscriptionButtonEnabled &&
                    !isConfigTagEnabled('subscription.subscription_payment_button')
                  }
                >
                  <PaymentButton>
                    <SubscriptionButtonList />
                  </PaymentButton>
                </RouteGuard>
              }
            />
            <Route
              path=":id/:entity_name"
              element={
                <RouteGuard
                  additionalCondition={(user) =>
                    user.isAllowedView('payment_buttons') && user.isSubscriptionButtonEnabled
                  }
                >
                  <SubscriptionButtonDetails />
                </RouteGuard>
              }
            />
          </Route>

          <Route
            path="subscriptions/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isAllowedView('subscriptions') &&
                  !isConfigTagEnabled('subscription.subscription')
                }
              >
                <Subscriptions />
              </RouteGuard>
            }
          >
            <Route
              index
              element={
                <RouteGuard additionalCondition={(user) => !user.isChargeAtWillEnabled}>
                  <SubscriptionsList />
                </RouteGuard>
              }
            />

            <Route
              path="batchuploads/*"
              element={
                <RouteGuard additionalCondition={(user) => user.isChargeAtWillEnabled}>
                  <HostedEmanadateBatches />
                </RouteGuard>
              }
            />

            <Route
              path="settings/*"
              element={
                <RouteGuard additionalCondition={(user) => !user.isChargeAtWillEnabled}>
                  <SubscriptionSettings />
                </RouteGuard>
              }
            />
          </Route>

          <Route
            path="plans/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isAllowedView('subscriptions') && !user.isChargeAtWillEnabled
                }
              >
                <Subscriptions />
              </RouteGuard>
            }
          >
            <Route
              index
              element={
                <RouteGuard additionalCondition={(user) => !user.isChargeAtWillEnabled}>
                  <PlansList docUrl="https://razorpay.com/docs/subscriptions/" />
                </RouteGuard>
              }
            />
          </Route>

          <Route
            path="recurring_payments/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isAllowedView('subscriptions') &&
                  user.isChargeAtWillEnabled &&
                  user.isRegistrationLinkTokenAndPaymentsEnabled
                }
              >
                <Subscriptions />
              </RouteGuard>
            }
          >
            <Route
              index
              element={
                <RouteGuard
                  additionalCondition={(user) => user.isRegistrationLinkTokenAndPaymentsEnabled}
                >
                  <RecurringPayments />
                </RouteGuard>
              }
            />
          </Route>

          <Route
            path="tokens/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isAllowedView('subscriptions') &&
                  user.isChargeAtWillEnabled &&
                  user.isRegistrationLinkTokenAndPaymentsEnabled
                }
              >
                <Subscriptions />
              </RouteGuard>
            }
          >
            <Route
              index
              element={
                <RouteGuard
                  additionalCondition={(user) => user.isRegistrationLinkTokenAndPaymentsEnabled}
                >
                  <TokensList />
                </RouteGuard>
              }
            />
          </Route>

          <Route
            path="registration_links/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isAllowedView('subscriptions') && user.isChargeAtWillEnabled
                }
              >
                <Subscriptions />
              </RouteGuard>
            }
          >
            <Route
              index
              element={
                <RouteGuard>
                  <RegistrationLinksList />
                </RouteGuard>
              }
            />
          </Route>

          <Route
            path="payment-metrics/*"
            element={
              <RouteGuard
                additionalCondition={(user) => user.isCheckoutAnalyticsEnabled && user.isOrgRZP}
              >
                <PaymentMetrics />
              </RouteGuard>
            }
          />

          <Route
            path="affordability/*"
            element={
              <RouteGuard
                additionalCondition={(user) => user.isShowAffordabilityWidget && user.isOrgRZP}
              >
                <Affordability />
              </RouteGuard>
            }
          />

          <Route
            path="qr_codes/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isAllowedView('qr_codes') && !isConfigTagEnabled('qr_code.qr_code')
                }
              >
                <QRCodes />
              </RouteGuard>
            }
          />

          <Route
            path="stores/*"
            element={
              <RouteGuard
                additionalCondition={(_user) =>
                  _user.isAllowedView('stores') && _user.isStoresEnabled
                }
              >
                <Stores />
              </RouteGuard>
            }
          />

          <Route
            path="api-keys/*"
            element={
              <RouteGuard
                additionalCondition={(_user) =>
                  _user.isAllowedView('api_keys') &&
                  (_user.isProductLedOnboardingRZP || _user.isApiKeysRevampEnabled) &&
                  _user.activated
                }
              >
                <ApiKeysAndPlugins />
              </RouteGuard>
            }
          />

          <Route
            path="customers/*"
            element={
              <RouteGuard additionalCondition={() => !isConfigTagEnabled('customers.customer')}>
                <tabbed-container>
                  <header id="invoicing-header">
                    <NavLink to="/customers">Customers</NavLink>
                  </header>
                  <content>
                    <Routes>
                      <Route
                        index
                        element={
                          <RouteGuard>
                            <Customers />
                          </RouteGuard>
                        }
                      />
                    </Routes>
                  </content>
                </tabbed-container>
              </RouteGuard>
            }
          />

          <Route
            path="connected-banking/icici-linked-ca/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isICICILinkedCAEnabled || getXCAStatus(user).showState === 'neostone-tracker'
                }
              >
                <ConnectedBanking />
              </RouteGuard>
            }
          />

          <Route
            path="razorpayx/*"
            element={
              <RouteGuard
                additionalCondition={(user) => user.isShowRazorpayXWidgetEnabled && user.isOrgRZP}
              >
                <RazorpayXWidget />
              </RouteGuard>
            }
          />

          <Route
            path="route/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isAllowedView('marketplace') && !isConfigTagEnabled('route.marketplace')
                }
              >
                <Marketplace />
              </RouteGuard>
            }
          />

          <Route
            path="bbps/*"
            element={
              <RouteGuard
                additionalCondition={(user) => user.isAllowedView('bbps') && user.isBbpsEnabled}
              >
                <BbpsComponent />
              </RouteGuard>
            }
          />

          <Route
            path="smartcollect/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isAllowedView('virtual_accounts') &&
                  !isConfigTagEnabled('smart_collect.virtual_accounts')
                }
              >
                <SmartCollect />
              </RouteGuard>
            }
          >
            <Route
              path="virtualaccounts/*"
              element={
                <RouteGuard>
                  <VirtualAccountsList />
                </RouteGuard>
              }
            />

            <Route
              path="payments/*"
              element={
                <RouteGuard>
                  <SmartCollectPaymentsList />
                </RouteGuard>
              }
            />
            <Route
              path="batchuploads/*"
              element={
                <RouteGuard>
                  <BatchExpiryUpdate />
                </RouteGuard>
              }
            />
          </Route>

          <Route
            path="virtualaccounts/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isAllowedView('virtual_accounts') &&
                  !isConfigTagEnabled('smart_collect.virtual_accounts')
                }
              >
                <SmartCollect />
              </RouteGuard>
            }
          >
            <Route
              index
              element={
                <RouteGuard>
                  <VirtualAccountsList />
                </RouteGuard>
              }
            />
          </Route>

          <Route
            path="pricing-plans/*"
            element={
              <RouteGuard additionalCondition={(user) => user?.isBundlePricingEnabled}>
                <MyAccount>
                  <PricingPlans />
                </MyAccount>
              </RouteGuard>
            }
          />

          <Route
            path="trustedbadge/*"
            element={
              <RouteGuard additionalCondition={(user) => isTrustedBadgeAllowed(user, extraConfig)}>
                <MyAccount>
                  <TrustedBadge />
                </MyAccount>
              </RouteGuard>
            }
          />

          <Route
            path="profile/*"
            element={
              <RouteGuard
                additionalCondition={(user) => isProfileViewAllowed(user) || !user.userRole}
              >
                <MyAccount>
                  <Profile />
                </MyAccount>
              </RouteGuard>
            }
          />
          <Route
            path="website-app-details/*"
            element={
              <RouteGuard
                additionalCondition={() =>
                  true && !isConfigTagEnabled('contact.website_app_details')
                }
              >
                <MyAccount>
                  <WebsiteAppDetails />
                </MyAccount>
              </RouteGuard>
            }
          />
          <Route
            path="addfunds/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isAllowedView('add_funds') && !isConfigTagEnabled('account.balances')
                }
              >
                <MyAccount>
                  <Balances />
                </MyAccount>
              </RouteGuard>
            }
          />

          <Route
            path="credits/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isAllowedView('credits') && !isConfigTagEnabled('account.credits')
                }
              >
                <MyAccount>
                  <Credits />
                </MyAccount>
              </RouteGuard>
            }
          />

          <Route path="ticket-support/*">
            <Route
              path="tickets/*"
              element={
                <RouteGuard
                  myRole="owner admin"
                  additionalCondition={(user) =>
                    user.isFdTicketsEnabled &&
                    !user.isComdelApiEnabled &&
                    !isConfigTagEnabled('account.support_history') &&
                    user?.isBundlePricingEnabled
                  }
                >
                  <MyAccount>
                    {user.isMobileSignupCareActive ? <TicketsContainer /> : <Tickets />}
                  </MyAccount>
                </RouteGuard>
              }
            />

            <Route
              path=":instance/:id/:ticketType/conversation/*"
              element={
                <RouteGuard>
                  <MyAccount>
                    <Conversations />
                  </MyAccount>
                </RouteGuard>
              }
            />
          </Route>

          <Route
            path="referrals/*"
            element={
              <RouteGuard
                featureEnabled="Referral"
                additionalCondition={(user) => user.isAllowedView('referrals')}
              >
                <MyAccount>
                  <Referrals />
                </MyAccount>
              </RouteGuard>
            }
          />

          <Route
            path="team/*"
            element={
              <RouteGuard additionalCondition={(user) => user.isAllowedTeamManagement}>
                <MyAccount>
                  <ManageTeam />
                </MyAccount>
              </RouteGuard>
            }
          />

          <Route path="developers/*">
            <Route
              path="*"
              element={
                <RouteGuard
                  additionalCondition={(user) =>
                    !isMobileResolution() &&
                    user.isAllowedView('developers_console') &&
                    (user.isDeveloperConsoleEnabled || user.isDeveloperConsoleWebhooksTabEnabled)
                  }
                >
                  <Developers />
                </RouteGuard>
              }
            />
            <Route
              path="webhooks/:id"
              element={
                <RouteGuard
                  additionalCondition={(user) =>
                    user.isAllowedView('developers_console') &&
                    user.isDeveloperConsoleWebhooksTabEnabled
                  }
                >
                  <DevelopersWebhooks />
                </RouteGuard>
              }
            />
          </Route>

          <Route
            path="/config/*"
            element={
              <RouteGuard additionalCondition={(user) => user.isAllowedView('configuration')}>
                <Settings />
              </RouteGuard>
            }
          />

          <Route
            path="/keys/*"
            element={
              <RouteGuard additionalCondition={(user) => user.isAllowedView('api_keys')}>
                <Settings />
              </RouteGuard>
            }
          />

          <Route
            path="/webhooks/*"
            element={
              <RouteGuard additionalCondition={(user) => user.isAllowedView('webhooks')}>
                <Settings />
              </RouteGuard>
            }
          />

          <Route path="/reminders/*" element={<Settings />} />
          <Route
            path="/applications/*"
            element={
              <RouteGuard additionalCondition={(user) => user.isAllowedView('applications')}>
                <Settings />
              </RouteGuard>
            }
          />

          <Route
            path="payment-methods/*"
            element={
              <RouteGuard additionalCondition={(user) => isPaymentMethodEnabled(user, mode)}>
                <PaymentMethods />
              </RouteGuard>
            }
          />

          <Route
            path="offers/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isAllowedView('offers') && !isConfigTagEnabled('offers.offers')
                }
              >
                <Offers />
              </RouteGuard>
            }
          />

          <Route
            path="checkout-rewards/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  user.isAllowedView('checkoutrewards') &&
                  !isConfigTagEnabled('checkout_rewards.checkout_rewards')
                }
              >
                <CheckoutRewards />
              </RouteGuard>
            }
          />

          <Route
            path="business-settings/*"
            element={
              <RouteGuard>
                <BusinessSettings />
              </RouteGuard>
            }
          />

          <Route
            path="optimizer/*"
            element={
              <RouteGuard additionalCondition={(user) => user.isAllowedView('optimizer')}>
                <Navigator />
              </RouteGuard>
            }
          />

          <Route
            path="paypal_onboard_redirect/*"
            element={
              <RouteGuard>
                <PaypalOnboardRedirect />
              </RouteGuard>
            }
          />

          <Route path="capital/*">
            <Route path=":product">
              <Route
                path="repayments-schedule/*"
                additionalCondition={canViewCashAdvanceProduct}
                element={
                  <RouteGuard>
                    <RepaymentsSchedule />
                  </RouteGuard>
                }
              />

              <Route
                path=":section/*"
                element={
                  <RouteGuard
                    defaultPath="/capital/line-of-credit"
                    additionalCondition={canViewCashAdvanceProduct}
                  >
                    <CashAdvance />
                  </RouteGuard>
                }
              />
              <Route
                index
                element={
                  <RouteGuard
                    additionalCondition={(user) =>
                      canViewCashAdvanceProduct(user) || canViewLOCEMIProduct(user)
                    }
                  >
                    <FlashCreditLandingPage />
                  </RouteGuard>
                }
              />
              <Route
                path="apply"
                element={
                  <RouteGuard>
                    <LoanDetails />
                  </RouteGuard>
                }
              />
            </Route>
            <Route>
              <Route path="loans/*">
                <Route index element={<Navigate to="/capital/loans/apply" replace />} />
                <Route
                  path=":section/*"
                  element={
                    <RouteGuard>
                      <LoansCollections />
                    </RouteGuard>
                  }
                />
              </Route>
            </Route>

            <Route
              path="non-fldg-loans/*"
              element={
                <RouteGuard>
                  <NonFldgLoans />
                </RouteGuard>
              }
            />

            <Route
              path="corporate-cards/*"
              element={
                <RouteGuard>
                  <CorporateCards />
                </RouteGuard>
              }
            />
          </Route>

          <Route
            path="payroll/*"
            element={
              <RouteGuard additionalCondition={getIsPayrollWidgetEnabled}>
                <PayrollWidget />
              </RouteGuard>
            }
          />

          <Route
            path="website-app-settings/*"
            element={
              <RouteGuard>
                <WebsiteAndAppSettings />
              </RouteGuard>
            }
          />

          <Route
            path="checkout-settings/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  isConfigurationViewAllowed(user) || isTrustedBadgeAllowed(user, extraConfig)
                }
              >
                <CheckoutSettings />
              </RouteGuard>
            }
          />

          <Route
            path="notification-settings/*"
            element={
              <RouteGuard additionalCondition={isConfigurationViewAllowed}>
                <NotificationSettings />
              </RouteGuard>
            }
          />

          <Route
            path="payments-and-refunds-settings/*"
            element={
              <RouteGuard>
                <PaymentsAndRefundsSettings />
              </RouteGuard>
            }
          />

          <Route
            path="pricing/*"
            element={
              <RouteGuard additionalCondition={(user) => user?.isBundlePricingEnabled}>
                <Pricing />
              </RouteGuard>
            }
          />

          <Route
            path="bank-accounts-settlements/*"
            element={
              <RouteGuard additionalCondition={isProfileViewAllowed}>
                <BankAccountsAndSettlements />
              </RouteGuard>
            }
          />

          {/*
          <Route
            path="international-settings/*"
            element={
              <RouteGuard additionalCondition={shouldShowFIRCSection}>
                <InternationalSettings />
              </RouteGuard>
            }
          />

          {/*
          <Route
            path="/pos/:page?"
            element={
              <RouteGuard additionalCondition={() => isPosExperimentEnabled(splitz)}>
                <POS />
              </RouteGuard>
            }
          /> */}

          <Route
            path="reports/*"
            element={
              <RouteGuard
                additionalCondition={(user) =>
                  (user.isAllowedView('reports') || user.isCareHealthOwner) &&
                  user.hideForNIASupportRole
                }
              >
                <MerchantReports />
              </RouteGuard>
            }
          />
        </Routes>
      </Suspense>
    );
  };

  UNSAFE_componentWillMount() {
    this.setBaseLocation(this.props.location);
    this.toggleRasieTicketModal(this.props);
  }

  componentDidMount() {
    this.listenTrackEvents();
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    this.setBaseLocation(nextProps.location);
    this.showSliderView();

    if (nextProps.location.hash !== this.props.location.hash) {
      this.toggleRasieTicketModal(nextProps);
    }
  }

  showSliderView() {
    if (this.detailView && this.baseLocation) {
      this.props.openSlider();
    }
  }

  closeModalView = () => {
    document.body.classList.remove('noscroll');
    this.props.history.replace(this.baseLocation.pathname);
  };

  getOverlayCustomClass = () => {
    if (this.props.location?.pathname?.includes('developers')) {
      return 'developers-container';
    }

    return '';
  };

  render() {
    const { mode, user, fullPageView, isWebView, isMobile } = this.props;

    let DetailView = this.detailView;
    const BaseView = this.baseLocation ? this.getBaseView() : null;
    const isMobileSearchEnabled = user.isUniversalSearchEnabled && isMobile;
    let ModalFormView = this.modalView;

    const overlayCustomClass = this.getOverlayCustomClass();

    if (DetailView) {
      DetailView = BaseView ? (
        <Slider overlayCustomClass={overlayCustomClass} closeUrl={this.baseLocation}>
          {' '}
          <ErrorBoundary resetOnProps>
            <Suspense fallback={<Loader />}>
              <DetailView {...this.detailProps} closeUrl={this.baseLocation.pathname} />{' '}
            </Suspense>
          </ErrorBoundary>
        </Slider>
      ) : (
        <Suspense fallback={<Loader />}>
          <DetailView {...this.detailProps} />
        </Suspense>
      );
    } else if (ModalFormView) {
      ModalFormView = BaseView ? (
        <ModalMask
          maskClosable={false}
          onClose={this.closeModalView}
          class={ModalFormView.MODAL_MASK_CLASS}
        >
          <Suspense fallback={<Loader />}>
            <ModalFormView
              {...this.detailProps}
              onClose={this.closeModalView}
              closeUrl={BaseView ? this.baseLocation.pathname : undefined}
            />
          </Suspense>
        </ModalMask>
      ) : (
        <Suspense fallback={<Loader />}>
          <ModalFormView {...this.detailProps} />
        </Suspense>
      );
    }
    return (
      // to add a new class alognside main-content if we are in the test mode and in m-web
      <main
        class={classList(
          !fullPageView && !isWebView && 'main-content',
          isMobileSearchEnabled && !fullPageView && 'search-header',
          mode === 'test' && isMobileDevice() ? 'test-mode' : '',
        )}
      >
        <ErrorBoundary resetOnProps>
          <Suspense fallback={<Loader />}>
            {BaseView}
            {DetailView}
            {ModalFormView}
            <MultiSlider />
            {window?.RZP?.appName !== 'businessbanking' && (
              <Suspense fallback={null}>
                <HelpSection user={user} />
              </Suspense>
            )}
          </Suspense>
        </ErrorBoundary>
      </main>
    );
  }
}

export default withSplitzService(withRouter(Content));
