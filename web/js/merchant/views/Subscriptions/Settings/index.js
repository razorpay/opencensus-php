import React from 'react';
import { connect } from 'react-redux';

// eslint-disable-next-line no-restricted-imports
import { withI18Service } from 'common/i18';
import Amount from 'common/ui/Amount';
import Banner from 'common/ui/Banner';
import Alert from 'common/ui/Forms/Alert';
import SwitchField from 'common/ui/Forms/SwitchField';
// eslint-disable-next-line no-restricted-imports
import HeaderAction from 'common/ui/HeaderAction';
import Spinner from 'common/ui/Spinner';
import { classList, findBy, rupeesToPaise } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import DocsLink, { DocLink } from 'merchant/components/DocsLink';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import { RZPFeatures } from 'merchant/helpers/data';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';
import { fetchSettings, saveSettings } from 'merchant/reducers/subscriptions';
import analytics from 'merchant/views/Subscriptions/analytics';
import {
  CARD_AFA_MAX_LIMIT,
  GATEWAY_MAX_LIMIT,
  UPI_AFA_MAX_LIMIT,
  EMANDATE_MAX_LIMIT,
  UPI_MAX_LIMIT_FOR_NON_BFSI,
  DEFAULT_TOUCH_N_GO_MAX_LIMIT,
} from 'merchant/views/Subscriptions/constants';
import { showNotification } from 'merchant_common/reducers/notifications';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { Alert as BladeAlert, Link } from '@razorpay/blade/components';
import { useSplitzService, withSplitzService } from 'common/splitz';
import { isExperimentEnabled } from 'common/splitz/utils';

const PAYMENT_METHODS = {
  UPI: 'upi',
  CARD: 'card',
  EMANDATE: 'emandate',
  TOUCH_N_GO_WALLET: 'touchngo_wallet',
};

const enableDisableMap = {
  1: 'enable',
  0: 'disable',
};

const methodToAlertCTAMap = {
  [PAYMENT_METHODS.CARD]: 'Enable cards recurring',
  [PAYMENT_METHODS.UPI]: 'Enable UPI autopay',
  [PAYMENT_METHODS.EMANDATE]: 'Enable eMandate',
  // no CTA for Touch 'n Go Wallet as it is by defalt always enabled as payment method
};

const methodToTitleMap = {
  [PAYMENT_METHODS.CARD]: 'Card recurring',
  [PAYMENT_METHODS.UPI]: 'UPI autopay',
  [PAYMENT_METHODS.EMANDATE]: 'eMandate',
  [PAYMENT_METHODS.TOUCH_N_GO_WALLET]: "Touch 'n Go Wallet", // Touch 'n Go Wallet is by defalt always enabled as payment method. Only added for notification text
};

const methodToRoutesMap = {
  [PAYMENT_METHODS.CARD]: ROUTES_INFO.CARDS,
  [PAYMENT_METHODS.UPI]: ROUTES_INFO.UPI_QR,
  [PAYMENT_METHODS.EMANDATE]: ROUTES_INFO.NETBANKING,
  // no route for Touch 'n Go Wallet as it is by defalt always enabled as payment method
};

const isToggleEnabled = (methodName) => (settings) => {
  const paymentMethod = findBy(settings.items, 'name', methodName) || {};
  return paymentMethod.setting_enabled === '1';
};

// Before enabling the subscriptions toggle, payment method must be enabled
const isMethodEnabled = (methodName) => (settings) => {
  const paymentMethod = findBy(settings.items, 'name', methodName) || {};
  return paymentMethod.method_enabled ? paymentMethod.method_enabled === '1' : true;
};

const getMaxAmount = (methodName) => (settings) => {
  const paymentMethod = findBy(settings.items, 'name', methodName) || {};
  return paymentMethod.max_amount;
};

const isSubsciptionsToggleAllowed = (splitz) => {
  const { abExperiments } = splitz || { abExperiments: { subscriptions_toggle: undefined } };
  if (!abExperiments?.subscriptions_toggle) return false;
  return isExperimentEnabled(abExperiments.subscriptions_toggle);
};

const cardDescription = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: (
    <>
      Accept recurring payments via debit & credit cards for your subscriptions in any of our{' '}
      <DocLink
        className="inline-doc"
        target="_blank"
        href="https://razorpay.com/docs/payments/payments/international-payments/#supported-currencies"
      >
        supported international currencies.
      </DocLink>
    </>
  ),
  [ORG_CUSTOM_CODE_MAP.CURLEC]: (
    <>Accept recurring payments via debit & credit cards for your subscriptions</>
  ),
};

const cardNote = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: (
    <>
      <strong>Note:</strong> Only limited cards are supported due to new payment regulations by RBI.{' '}
      <DocLink
        className="inline-doc"
        target="_blank"
        href="https://razorpay.com/docs/subscriptions/bank-options/#card-networks"
      >
        View supported cards
      </DocLink>
    </>
  ),
  [ORG_CUSTOM_CODE_MAP.CURLEC]: <div />,
};

@connect(
  (state, ownProps) => ({
    // if experiment is not enabled, then show the default settings
    settings: isSubsciptionsToggleAllowed(ownProps.splitz)
      ? state.subscriptions.settings
      : {
          loading: false,
          items: [],
          error: null,
        },
    user: state.session.user,
    org: state.session.org,
  }),
  { fetchSettings, saveSettings, showNotification },
)
class SubscriptionsSettings extends React.Component {
  state = {};

  componentDidMount() {
    if (isSubsciptionsToggleAllowed(this.props.splitz)) {
      this.props.fetchSettings();
    }
  }

  onToggleChange = (methodName) => (isChecked, cb) => {
    const paymentMethod = findBy(this.props.settings.items, 'name', methodName) || {};

    const checked = paymentMethod.setting_enabled === '1';

    const data = {
      name: methodName,
      setting_enabled: checked ? '0' : '1',
    };

    if (paymentMethod.id) {
      data.id = paymentMethod.id;
    }

    const eventLabel = `subscription.setting.${
      enableDisableMap[data.setting_enabled]
    }_${methodName}`;
    analytics.track(eventLabel);
    selfServeTrackInitiate({
      selfServeAction: `Subscriptions ${methodName} ${checked ? 'disabled' : 'enabled'}`,
      page: 'Settings',
      screen: 'Subscriptions',
    });
    return this.props
      .saveSettings(data)
      .then(() => {
        cb(true);
        selfServeTrackSuccess({
          selfServeAction: `Subscriptions ${methodName} ${checked ? 'disabled' : 'enabled'}`,
          page: 'Settings',
          screen: 'Subscriptions',
        });
        this.props.showNotification({
          type: 'success',
          message: `${methodToTitleMap[methodName]} as a subscriptions payment method has been ${
            checked ? 'disabled' : 'enabled'
          } successfully`,
        });
      })
      .catch(({ errors }) => {
        cb(false);
        this.props.showNotification({
          type: 'error',
          message: errors[0],
        });
      });
  };

  render() {
    const { settings, user, org, i18 } = this.props;
    const { isConfigTagEnabled } = i18;

    const refConfigTagEnabled = !isConfigTagEnabled('subscriptions.emandate');

    const orgCode = org?.custom_code || 'rzp';
    const cardDescriptionText = cardDescription[orgCode] || cardDescription.rzp;
    const cardNoteText = cardNote[orgCode] || cardNote.rzp;

    const merchantCurrency = user?.merchant?.currency;
    if (settings.loading) {
      return (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    }

    return (
      <div class="Subscriptions--Settings content-wrapper">
        <HeaderAction responsive>
          <div class="btn-toolbar pull-right">
            <TakeATourButton
              feature={RZPFeatures.SUBSCRIPTIONS}
              onClick={() => analytics.track('setting.search.help')}
            />

            <DocsLink
              url="https://razorpay.com/docs/subscriptions/"
              onClick={() => analytics.track('setting.search.documentation')}
            />
          </div>
        </HeaderAction>

        <div
          class="panel panel-default panel-theme"
          style={
            user.isEmandateOnSubscriptionEnabled && refConfigTagEnabled ? {} : { maxWidth: '854px' }
          }
        >
          {settings.error ? (
            <Alert type="error" message={settings.errors} showDismiss={false} />
          ) : (
            <>
              <div class="panel-heading">
                <span class="title">Payment Methods</span>{' '}
                <DocsLink
                  title="Know More"
                  url="https://razorpay.com/docs/subscriptions/dashboard/settings/#steps"
                />
              </div>
              <div class="panel-body">
                <div class="row">
                  <div
                    class={classList(
                      user.isEmandateOnSubscriptionEnabled && refConfigTagEnabled
                        ? 'col-md-4'
                        : 'col-md-6',
                      'column',
                    )}
                  >
                    <ToggleCard
                      title={
                        <>
                          <i class="i i-card m-r" /> Card
                        </>
                      }
                      methodName={PAYMENT_METHODS.CARD}
                      onToggleChange={this.onToggleChange(PAYMENT_METHODS.CARD)}
                      checked={isToggleEnabled(PAYMENT_METHODS.CARD)(settings)}
                      methodEnabled={isMethodEnabled(PAYMENT_METHODS.CARD)(settings)}
                      description={() => cardDescriptionText}
                      info={() => (
                        <>
                          Accept payments upto{' '}
                          <strong>
                            {' '}
                            <Amount
                              value={
                                user.isOrgCurlec
                                  ? getMaxAmount(PAYMENT_METHODS.CARD)(settings)
                                  : GATEWAY_MAX_LIMIT
                              }
                              currency={merchantCurrency}
                              hidePaisa
                            />
                          </strong>
                          <br />
                          Payments above{' '}
                          <Amount
                            value={rupeesToPaise(CARD_AFA_MAX_LIMIT)}
                            currency={merchantCurrency}
                            hidePaisa
                          />{' '}
                          will ask the customer for OTP verification as well.
                        </>
                      )}
                      note={() => cardNoteText}
                      history={this.props.history}
                    />
                  </div>

                  {!user.isOrgCurlec && (
                    <div
                      class={classList(
                        user.isEmandateOnSubscriptionEnabled && refConfigTagEnabled
                          ? 'col-md-4'
                          : 'col-md-6',
                        'column',
                      )}
                    >
                      <ToggleCard
                        title={
                          <>
                            <i class="i i-upi m-r" /> UPI
                          </>
                        }
                        methodName={PAYMENT_METHODS.UPI}
                        onToggleChange={this.onToggleChange(PAYMENT_METHODS.UPI)}
                        checked={isToggleEnabled(PAYMENT_METHODS.UPI)(settings)}
                        methodEnabled={isMethodEnabled(PAYMENT_METHODS.UPI)(settings)}
                        description={
                          <>
                            Accept recurring payments via UPI apps like PhonePe, Paytm & BHIM for
                            your subscriptions. Only supports Indian currency.
                          </>
                        }
                        info={() => (
                          <>
                            Accept payments upto{' '}
                            <strong>
                              {' '}
                              <Amount
                                value={UPI_MAX_LIMIT_FOR_NON_BFSI}
                                currency={merchantCurrency}
                                hidePaisa
                              />
                            </strong>{' '}
                            (For BFSI:{' '}
                            <Amount
                              value={GATEWAY_MAX_LIMIT}
                              currency={merchantCurrency}
                              hidePaisa
                            />
                            )
                            <br />
                            Payments above{' '}
                            <Amount
                              value={UPI_AFA_MAX_LIMIT}
                              currency={merchantCurrency}
                              hidePaisa
                            />{' '}
                            will ask the customer for UPI PIN verification as well.
                          </>
                        )}
                        history={this.props.history}
                      />
                    </div>
                  )}

                  {user.isOrgCurlec && (
                    <div
                      class={classList(
                        user.isEmandateOnSubscriptionEnabled && refConfigTagEnabled
                          ? 'col-md-4'
                          : 'col-md-6',
                        'column',
                      )}
                    >
                      <ToggleCard
                        title={
                          <>
                            <i class="i i-upi m-r" /> Touch 'n Go Wallet
                          </>
                        }
                        methodName={PAYMENT_METHODS.TOUCH_N_GO_WALLET}
                        onToggleChange={this.onToggleChange(PAYMENT_METHODS.TOUCH_N_GO_WALLET)}
                        checked={isToggleEnabled(PAYMENT_METHODS.TOUCH_N_GO_WALLET)(settings)}
                        // Touch 'n Go Wallet is by defalt always enabled as payment method
                        methodEnabled={isMethodEnabled(PAYMENT_METHODS.TOUCH_N_GO_WALLET)(settings)}
                        description={
                          <>
                            Accept recurring payments directly via Wallet for your subscriptions.
                            Only supports Malaysian currency.
                          </>
                        }
                        info={() => (
                          <>
                            Accept payments upto{' '}
                            <strong>
                              {' '}
                              <Amount
                                value={
                                  getMaxAmount(PAYMENT_METHODS.TOUCH_N_GO_WALLET)(settings) ||
                                  DEFAULT_TOUCH_N_GO_MAX_LIMIT
                                }
                                currency={merchantCurrency}
                                hidePaisa
                              />
                            </strong>
                          </>
                        )}
                        history={this.props.history}
                      />
                    </div>
                  )}

                  {user.isEmandateOnSubscriptionEnabled && refConfigTagEnabled && (
                    <div class="col-md-4 column">
                      <ToggleCard
                        title={
                          <>
                            <i class="i i-bank m-r" /> eMandate
                          </>
                        }
                        methodName={PAYMENT_METHODS.EMANDATE}
                        onToggleChange={this.onToggleChange(PAYMENT_METHODS.EMANDATE)}
                        checked={isToggleEnabled(PAYMENT_METHODS.EMANDATE)(settings)}
                        methodEnabled={isMethodEnabled(PAYMENT_METHODS.EMANDATE)(settings)}
                        description="Accept recurring payments directly via bank accounts for your subscriptions. Only supports Indian currency."
                        info={
                          <>
                            Accept payments upto:{' '}
                            <strong>
                              <Amount
                                value={EMANDATE_MAX_LIMIT}
                                currency={merchantCurrency}
                                hidePaisa
                              />
                            </strong>
                          </>
                        }
                        history={this.props.history}
                      />
                    </div>
                  )}
                </div>
              </div>
            </>
          )}
        </div>
      </div>
    );
  }
}

const ToggleCard = ({
  title,
  checked,
  info = null,
  description,
  onToggleChange,
  note,
  methodName,
  methodEnabled = true, // Payment method is enabled by default
  history,
}) => {
  const splitz = useSplitzService();
  const canToggleSubscriptions = isSubsciptionsToggleAllowed(splitz);

  const showToggle = canToggleSubscriptions && methodEnabled;
  const showRedirectionAlert = canToggleSubscriptions && !methodEnabled;

  return (
    <div class="panel panel-default ToggleCard">
      <div class="panel-heading">
        <span class="title">{title}</span>
        {showToggle && (
          <span class="pull-right toggler-btn">
            <SwitchField checked={checked} onChange={onToggleChange} type="prime" />
            <strong class={classList('m-l', checked ? 'text-primary' : 'text-faded')}>
              {checked ? 'Enabled' : 'Disabled'}
            </strong>
          </span>
        )}
      </div>
      <div class="panel-body">
        <div class="description">
          {typeof description === 'function' ? description() : description}
        </div>
        {info !== null && (
          <div class="m-t">
            <Banner className="settings-banner">
              <i class="i-info-outline m-r" />
              <div>{typeof info === 'function' ? info() : info}</div>
            </Banner>
          </div>
        )}
        {note && <div>{typeof note === 'function' ? note() : note}</div>}
      </div>

      {!canToggleSubscriptions && (
        <BladeAlert
          isDismissible={false}
          marginX="spacing.3"
          marginY="spacing.5"
          color="information"
          description={
            <>
              To enable this subscription, please fill this{' '}
              <Link size="small" href="https://razorpay-ind.freshdesk.com/support/tickets/new">
                form
              </Link>
            </>
          }
        />
      )}

      {/* Touch 'n Go Wallet is by defalt always enabled as payment method */}
      {showRedirectionAlert && (
        <BladeAlert
          isDismissible={false}
          marginX="spacing.3"
          marginY="spacing.5"
          color="notice"
          description={`${methodToTitleMap[methodName]} as a payment method is not enabled, please click below to raise a request`}
          actions={{
            primary: {
              onClick: () => {
                history.push(methodToRoutesMap[methodName]);
              },
              text: methodToAlertCTAMap[methodName],
            },
          }}
        />
      )}
    </div>
  );
};

export default withSplitzService(withI18Service(SubscriptionsSettings));
