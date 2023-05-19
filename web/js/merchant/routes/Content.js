import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import { ModalMask } from 'common/new-ui/Modal';
import Loader from 'common/ui/Loader';
import MultiSlider from 'common/ui/MultiSlider';
import Slider from 'common/ui/Slider';
import { analyticsTrack } from 'common/utils/analytics';
import {
  classList,
  getCommonAnalyticsProperties,
  isMobileResolution,
} from 'common/utils/rzp-utils';
import { isMobileDevice } from 'merchant/components/Home/data';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';
import Home from 'merchant/containers/Home/Index';
import { setActiveEntity, setBaseLocation, setSecActiveEntity } from 'merchant/reducers/app';
import { matchDetail, matchModal, supportHashMapping } from 'merchant/routes';
import { openSlider } from 'merchant_common/reducers/slider';
import qs from 'query-string';
import React, { Component, Suspense } from 'react';
import { connect } from 'react-redux';
import { NavLink, Redirect, Route, Switch, withRouter } from 'react-router-dom';
import RepaymentsSchedule from 'merchant/views/Capital/CashAdvance/RepaymentsSchedule';
import HandleIndex from './HandleIndex';
import lazy from './LazyLoader';
import { getXCAStatus } from 'common/ui/NotificationsDropdown/Neostone/common/utils';
import { getIsPayrollWidgetEnabled } from 'merchant/components/Sidebar/helpers';
import {
  isTrustedBadgeAllowed,
  isPaymentMethodEnabled,
  isProfileViewAllowed,
  isConfigurationViewAllowed,
} from 'merchant/views/AccountAndSettings/utils/conditionUtils';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import { canViewCashAdvanceProduct, canViewLOCEMIProduct } from 'merchant/views/Capital/utils';

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
  import(/* webpackChunkName: "Transactions" */ 'merchant/views/Transactions'),
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

const BBPS = lazy(() => import(/* webpackChunkName: "BBPS" */ 'merchant/views/BBPS'));
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

// Can be removed with old navigation removal
const TabbedContent = ({ headerId, navLabel, path, to, component }) => {
  return (
    <tabbed-container>
      <header id={headerId}>
        <NavLink to={to}>{navLabel}</NavLink>
      </header>
      <content>
        <Route path={path || to} component={component} />
      </content>
    </tabbed-container>
  );
};

// eslint-disable-next-line react/no-unsafe
@withRouter
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
export default class Content extends Component {
  setBaseLocation = (location) => {
    const matchDetailsRoute = matchDetail(location.pathname);
    const matchModalsRoute = matchModal(location.pathname);

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
    const { fullPageView, user, mode } = this.props;

    if (fullPageView) return fullPageView;

    const PaymentMethods = user.isAccountAndSettingsRevampEnabled ? PaymentMethodsV2 : Settings;

    return (
      <Suspense fallback={<Loader />}>
        <Switch location={this.baseLocation}>
          <Route path="/dashboard" component={Home} />
          <ShowWhenRoute
            path="/account-settings"
            component={AccountAndSettingsHome}
            additionalCondition={(user) =>
              user.isAccountAndSettingsRevampEnabled &&
              user.isAllowedMultiple(
                'webhooks applications configuration api_keys profile credits add_funds team referrals',
              )
            }
          />
          <ShowWhenRoute
            path="/partners"
            component={PartnerDashboard}
            additionalCondition={(user) => user.isPartner()}
          />

          <ShowWhenRoute
            path="/payments"
            component={Transactions}
            additionalCondition={(user) => user.isAllowedView('payments')}
          />
          <ShowWhenRoute
            path="/refunds"
            component={Transactions}
            additionalCondition={(user) => user.isAllowedView('refunds')}
          />
          <ShowWhenRoute
            path="/orders"
            component={Transactions}
            additionalCondition={(user) => user.isAllowedView('orders')}
          />
          <Route path="/disputes" component={Transactions} />

          <ShowWhenRoute
            path="/success-rate"
            component={Transactions}
            isTagDependent={true}
            additionalCondition={(currentUser) =>
              mode === 'live' &&
              currentUser.findTag('success_rate') &&
              currentUser.isAllowedView('success_rate')
            }
          />

          <ShowWhenRoute
            path="/settlements/:id(setl_.+)/"
            component={user.isSettlementV3RevampEnabled ? SettlementDetailsV3 : SettlementDetailsV2}
            additionalCondition={(user) =>
              user.isAllowedView('settlements') && user.hideForNIASupportRole
            }
          />

          <ShowWhenRoute
            path="/settlements"
            component={Settlements}
            additionalCondition={(user) =>
              user.isAllowedView('settlements') && user.hideForNIASupportRole
            }
          />
          <ShowWhenRoute
            path="/routeinstantsettlements"
            exact
            component={Settlements}
            additionalCondition={(user) =>
              user.isAllowedView('early_settlement') &&
              user.isOndemandRouteSettlementsEnabled &&
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Settlements)
            }
          />
          <ShowWhenRoute
            path="/instantsettlement_details/:id"
            component={InstantSettlementPayoutDetails}
            additionalCondition={(user) => user.isAllowedView('early_settlement')}
          />

          <ShowWhenRoute
            path="/instantsettlements"
            exact
            component={Settlements}
            additionalCondition={(user) => user.isAllowedView('early_settlement')}
          />

          <ShowWhenRoute
            path="/invoices"
            exact
            component={InvoicesContainer}
            additionalCondition={(user) =>
              user.isAllowedView('invoices') &&
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Invoices)
            }
          />
          <ShowWhenRoute
            path="/invoices/:id(inv_.+)"
            component={InvoicesNew}
            additionalCondition={(user) =>
              user.isAllowedView('invoices') &&
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Invoices)
            }
          />
          <ShowWhenRoute
            path="/invoices/new"
            component={InvoicesNew}
            additionalCondition={(user) =>
              user.isAllowedEdit('invoices') &&
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Invoices)
            }
          />
          <ShowWhenRoute
            path="/items"
            component={InvoicesContainer}
            additionalCondition={(user) => user.isAllowedView('invoices')}
          />

          <ShowWhenRoute
            path="/paymentlinks"
            component={PaymentLinks}
            additionalCondition={(user) =>
              user.isAllowedView('payment_links') &&
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentLinks)
            }
          />
          <ShowWhenRoute
            path="/payment-handle"
            component={PaymentHandle}
            additionalCondition={(user) =>
              user.isAllowedView('payment_handle') &&
              user.isPaymentHandleSplitzEnabled &&
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentHandle)
            }
          />
          <ShowWhenRoute
            path="/paymentpages/:id(pl_.+)/:entity_name(payments)"
            component={PaymentPagesDetails}
            additionalCondition={(user) =>
              user.isAllowedView('payment_pages') &&
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentPages)
            }
          />
          <ShowWhenRoute
            path="/paymentpages/storefront/:id(st_.+)/:entity_name(payments)"
            component={PaymentPagesDetails}
            additionalCondition={(user) =>
              user.isAllowedView('payment_pages') &&
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentPages) &&
              user.isPaymentPageStorefrontEnabled
            }
            isStorefrontPage
          />
          <ShowWhenRoute
            path="/paymentpages/batchuploads/:id/:title"
            component={BatchUploadContainer}
            additionalCondition={(user) => user.isPaymentPageFileUploadEnabled}
          />
          <ShowWhenRoute
            path="/paymentpages"
            component={PaymentPages}
            additionalCondition={(user) =>
              user.isAllowedView('payment_pages') &&
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentPages)
            }
          />
          <ShowWhenRoute
            path="/paymenthandle"
            component={PaymentHandle}
            additionalCondition={(user) =>
              user.isAllowedView('payment_handle') &&
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentHandle)
            }
          />
          <ShowWhenRoute
            path="/wallet"
            exact={false}
            component={Wallet}
            additionalCondition={(user) =>
              user.isIssuingDashboardEnabled || user.isIssuingBulkUploadEnabled
            }
          />

          <Route path="/super-checkout">
            <Redirect to="/magic" />
          </Route>

          <ShowWhenRoute
            path="/magic"
            component={MagicCheckout}
            additionalCondition={(user) => user.isMagicCheckoutEnabled}
          />

          <ShowWhenRoute
            path="/paymentbuttons/:id(pl_.+)/:entity_name(payments)"
            component={PaymentButtonsDetails}
            additionalCondition={(user) =>
              user.isAllowedView('payment_buttons') &&
              user.isPaymentButtonEnabledByRazorX &&
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentButtons)
            }
          />

          <ShowWhenRoute
            path="/paymentbuttons"
            component={PaymentButton}
            additionalCondition={(user) =>
              user.isAllowedView('payment_buttons') &&
              user.isPaymentButtonEnabledByRazorX &&
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.PaymentButtons)
            }
          />
          <ShowWhenRoute
            path="/subscription_buttons"
            component={PaymentButton}
            exact
            additionalCondition={(user) =>
              user.isAllowedView('subscription_buttons') && user.isSubscriptionButtonEnabled
            }
          />

          <ShowWhenRoute
            path="/subscription_buttons/:id(pl_.+)/:entity_name(payments)"
            exact
            component={SubscriptionButtonDetails}
            additionalCondition={(user) =>
              user.isAllowedView('payment_buttons') && user.isSubscriptionButtonEnabled
            }
          />

          <ShowWhenRoute
            path="/subscriptions"
            component={Subscriptions}
            additionalCondition={(user) =>
              user.isAllowedView('subscriptions') &&
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Subscriptions)
            }
          />
          <ShowWhenRoute path="/affordability" component={Affordability} />

          <ShowWhenRoute
            path="/affordability"
            component={Affordability}
            additionalCondition={(user) => user.isShowAffordabilityWidget && user.isOrgRZP}
          />

          <ShowWhenRoute
            path="/plans"
            component={Subscriptions}
            additionalCondition={(user) =>
              user.isAllowedView('subscriptions') && !user.isChargeAtWillEnabled
            }
          />

          <ShowWhenRoute
            path="/recurring_payments"
            component={Subscriptions}
            additionalCondition={(user) =>
              user.isAllowedView('subscriptions') &&
              user.isChargeAtWillEnabled &&
              user.isRegistrationLinkTokenAndPaymentsEnabled
            }
          />

          <ShowWhenRoute
            path="/qr_codes"
            component={QRCodes}
            additionalCondition={(user) =>
              user.isAllowedView('qr_codes') &&
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.QrCodes)
            }
          />

          <ShowWhenRoute
            path="/stores"
            component={Stores}
            additionalCondition={(_user) => _user.isAllowedView('stores') && _user.isStoresEnabled}
          />

          <ShowWhenRoute
            path="/api-keys"
            component={ApiKeysAndPlugins}
            additionalCondition={(_user) =>
              _user.isAllowedView('api_keys') &&
              (_user.isProductLedOnboardingRZP || _user.isApiKeysRevampEnabled) &&
              _user.activated
            }
          />

          <ShowWhenRoute
            path="/tokens"
            component={Subscriptions}
            additionalCondition={(user) =>
              user.isAllowedView('subscriptions') &&
              user.isChargeAtWillEnabled &&
              user.isRegistrationLinkTokenAndPaymentsEnabled
            }
          />
          <ShowWhenRoute
            path="/registration_links"
            component={Subscriptions}
            additionalCondition={(user) =>
              user.isAllowedView('subscriptions') && user.isChargeAtWillEnabled
            }
          />

          {!this.props?.user?.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Customers) && (
            <Route
              path="/customers"
              render={() => (
                <TabbedContent
                  headerId="invoicing-header"
                  to="/customers"
                  navLabel="Customers"
                  component={Customers}
                />
              )}
            />
          )}

          <ShowWhenRoute
            path="/connected-banking/icici-linked-ca"
            component={ConnectedBanking}
            additionalCondition={(user) =>
              user.isICICILinkedCAEnabled || getXCAStatus(user).showState === 'neostone-tracker'
            }
          />

          <ShowWhenRoute
            path="/razorpayx"
            component={RazorpayXWidget}
            additionalCondition={(user) => user.isShowRazorpayXWidgetEnabled && user.isOrgRZP}
          />

          <ShowWhenRoute
            path="/route"
            component={Marketplace}
            additionalCondition={(user) =>
              user.isAllowedView('marketplace') &&
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Marketplace)
            }
          />

          <ShowWhenRoute
            path="/bbps"
            component={BBPS}
            additionalCondition={(user) => user.isAllowedView('bbps') && user.isBbpsEnabled}
          />

          <ShowWhenRoute
            path={['/smartcollect', '/virtualaccounts']}
            component={SmartCollect}
            additionalCondition={(user) =>
              user.isAllowedView('virtual_accounts') &&
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.SmartCollect)
            }
          />

          <ShowWhenRoute
            path="/reports"
            component={MerchantReports}
            additionalCondition={(user) =>
              (user.isAllowedView('reports') || user.isCareHealthOwner) &&
              user.hideForNIASupportRole
            }
          />

          <ShowWhenRoute
            path="/pricing-plans"
            additionalCondition={(user) => user?.isBundlePricingEnabled}
            component={MyAccount}
          />
          <ShowWhenRoute
            path="/trustedbadge"
            component={MyAccount}
            additionalCondition={isTrustedBadgeAllowed}
          />
          <ShowWhenRoute
            path="/profile"
            component={MyAccount}
            additionalCondition={(user) => isProfileViewAllowed(user) || !user.userRole}
          />

          <ShowWhenRoute
            path="/website-app-details"
            component={MyAccount}
            additionalCondition={(user) =>
              true && !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.WebsiteAppDetails)
            }
          />

          <ShowWhenRoute
            path="/addfunds"
            component={MyAccount}
            additionalCondition={(user) => user.isAllowedView('add_funds')}
          />
          <ShowWhenRoute
            path="/credits"
            component={MyAccount}
            additionalCondition={(user) => user.isAllowedView('credits')}
          />
          <ShowWhenRoute
            path="/ticket-support/tickets"
            component={MyAccount}
            myRole="owner admin"
            additionalCondition={(user) =>
              user.isFdTicketsEnabled &&
              !user.isComdelApiEnabled &&
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.SupportHistory)
            }
          />
          <ShowWhenRoute
            path="/ticket-support/:instance/:id/:ticketType/conversation"
            component={MyAccount}
          />
          <ShowWhenRoute
            path="/referrals"
            component={MyAccount}
            featureEnabled="Referral"
            additionalCondition={(user) => user.isAllowedView('referrals')}
          />
          <ShowWhenRoute
            path="/team"
            component={MyAccount}
            additionalCondition={(user) => user.isAllowedTeamManagement}
          />
          <ShowWhenRoute
            path="/developers/webhooks/:id"
            exact
            component={DevelopersWebhooks}
            additionalCondition={(user) =>
              user.isAllowedView('developers_console') && user.isDeveloperConsoleWebhooksTabEnabled
            }
          />
          <ShowWhenRoute
            path="/developers"
            component={Developers}
            additionalCondition={(user) =>
              !isMobileResolution() &&
              user.isAllowedView('developers_console') &&
              (user.isDeveloperConsoleEnabled || user.isDeveloperConsoleWebhooksTabEnabled)
            }
          />
          <ShowWhenRoute
            path="/config"
            component={Settings}
            additionalCondition={(user) => user.isAllowedView('configuration')}
          />
          <ShowWhenRoute
            path="/keys"
            component={Settings}
            additionalCondition={(user) => user.isAllowedView('api_keys')}
          />
          <ShowWhenRoute
            path="/webhooks"
            component={Settings}
            additionalCondition={(user) => user.isAllowedView('webhooks')}
          />
          <ShowWhenRoute path="/reminders" component={Settings} />
          <ShowWhenRoute
            path="/applications"
            component={Settings}
            additionalCondition={(user) => user.isAllowedView('applications')}
          />
          <ShowWhenRoute
            path={ROUTES_INFO.PAYMENT_METHODS}
            component={PaymentMethods}
            additionalCondition={(user) => isPaymentMethodEnabled(user, mode)}
          />
          <ShowWhenRoute
            path="/offers"
            component={Offers}
            additionalCondition={(user) =>
              user.isAllowedView('offers') &&
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Offers)
            }
          />
          <ShowWhenRoute
            path="/checkout-rewards"
            component={CheckoutRewards}
            additionalCondition={(user) =>
              user.isAllowedView('checkoutrewards') &&
              !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Checkoutrewards)
            }
          />

          <ShowWhenRoute path="/business-settings" component={BusinessSettings} />

          <ShowWhenRoute
            path="/optimizer"
            component={Navigator}
            additionalCondition={(user) => user.isAllowedView('optimizer')}
          />
          <ShowWhenRoute path="/paypal_onboard_redirect" component={PaypalOnboardRedirect} />
          <ShowWhenRoute exact path="/capital/:product/apply" component={LoanDetails} />
          <Redirect exact from="/capital/loans" to="/capital/loans/apply" />
          <ShowWhenRoute
            path="/capital/cash-advance/repayments-schedule"
            component={RepaymentsSchedule}
          />
          <ShowWhenRoute
            path="/capital/cash-advance/:section"
            component={CashAdvance}
            additionalCondition={canViewCashAdvanceProduct}
            defaultPath="/capital/line-of-credit"
          />
          <ShowWhenRoute path="/capital/loans/:section" component={LoansCollections} />
          <ShowWhenRoute path="/capital/non-fldg-loans" component={NonFldgLoans} />
          <ShowWhenRoute
            exact
            path="/capital/:product(cash-advance|line-of-credit)"
            component={FlashCreditLandingPage}
            additionalCondition={(user) =>
              canViewCashAdvanceProduct(user) || canViewLOCEMIProduct(user)
            }
          />
          <ShowWhenRoute path="/capital/corporate-cards" component={CorporateCards} />
          <ShowWhenRoute
            path="/payroll"
            component={PayrollWidget}
            additionalCondition={getIsPayrollWidgetEnabled}
          />
          <Route path="/website-app-settings" component={WebsiteAndAppSettings} />
          <ShowWhenRoute
            path="/checkout-settings"
            component={CheckoutSettings}
            additionalCondition={(user) =>
              isConfigurationViewAllowed(user) || isTrustedBadgeAllowed(user)
            }
          />
          <ShowWhenRoute
            path="/notification-settings"
            component={NotificationSettings}
            additionalCondition={isConfigurationViewAllowed}
          />
          <ShowWhenRoute
            path="/payments-and-refunds-settings"
            component={PaymentsAndRefundsSettings}
          />
          <ShowWhenRoute
            path="/pricing"
            additionalCondition={(user) => user?.isBundlePricingEnabled}
            component={Pricing}
          />
          <ShowWhenRoute
            path="/bank-accounts-settlements"
            component={BankAccountsAndSettlements}
            additionalCondition={isProfileViewAllowed}
          />
          <Route exact path="/" component={HandleIndex} />
          <Route path="*" component={HandleIndex} />
        </Switch>
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
          isMobileSearchEnabled && 'search-header',
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
