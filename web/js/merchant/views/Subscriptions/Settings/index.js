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

const isEnabled = (methodName) => (settings) => {
  const paymentMethod = findBy(settings.items, 'name', methodName) || {};
  return paymentMethod.setting_enabled === '1';
};

const getMaxAmount = (methodName) => (settings) => {
  const paymentMethod = findBy(settings.items, 'name', methodName) || {};
  return paymentMethod.max_amount;
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
  (state) => ({
    settings: state.subscriptions.settings,
    user: state.session.user,
    org: state.session.org,
  }),
  { fetchSettings, saveSettings, showNotification },
)
class SubscriptionsSettings extends React.Component {
  state = {};

  componentDidMount() {
    this.props.fetchSettings();
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
          message: `Payment method ${methodName} ${checked ? 'disabled' : 'enabled'} successfully`,
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
                      onToggleChange={this.onToggleChange(PAYMENT_METHODS.CARD)}
                      checked={isEnabled(PAYMENT_METHODS.CARD)(settings)}
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
                        onToggleChange={this.onToggleChange(PAYMENT_METHODS.UPI)}
                        checked={isEnabled(PAYMENT_METHODS.UPI)(settings)}
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
                        onToggleChange={this.onToggleChange(PAYMENT_METHODS.TOUCH_N_GO_WALLET)}
                        checked={isEnabled(PAYMENT_METHODS.TOUCH_N_GO_WALLET)(settings)}
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
                        onToggleChange={this.onToggleChange(PAYMENT_METHODS.EMANDATE)}
                        checked={isEnabled(PAYMENT_METHODS.EMANDATE)(settings)}
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

const ToggleCard = ({ title, checked, info = null, description, onToggleChange, note }) => {
  return (
    <div class="panel panel-default ToggleCard">
      <div class="panel-heading">
        <span class="title">{title}</span>

        <span class="pull-right toggler-btn">
          <SwitchField checked={checked} onChange={onToggleChange} type="prime" />
          <strong class={classList('m-l', checked ? 'text-primary' : 'text-faded')}>
            {checked ? 'Enabled' : 'Disabled'}
          </strong>
        </span>
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
    </div>
  );
};

export default withI18Service(SubscriptionsSettings);
