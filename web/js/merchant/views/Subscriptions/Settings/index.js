import React from 'react';
import { connect } from 'react-redux';
// eslint-disable-next-line no-restricted-imports
import HeaderAction from 'common/ui/HeaderAction';
import { RZPFeatures } from 'merchant/helpers/data';
import { classList, findBy, rupeesToPaise } from 'common/utils/rzp-utils';

import DocsLink, { DocLink } from 'merchant/components/DocsLink';
import Amount from 'common/ui/Amount';
import Banner from 'common/ui/Banner';
import SwitchField from 'common/ui/Forms/SwitchField';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import Alert from 'common/ui/Forms/Alert';
import Spinner from 'common/ui/Spinner';

import { fetchSettings, saveSettings } from 'merchant/reducers/subscriptions';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  CARD_AFA_MAX_LIMIT,
  GATEWAY_MAX_LIMIT,
  UPI_AFA_MAX_LIMIT,
  EMANDATE_MAX_LIMIT,
  UPI_MAX_LIMIT_FOR_NON_BFSI,
} from 'merchant/views/Subscriptions/constants';
import analytics from 'merchant/views/Subscriptions/analytics';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';
import { withI18Service } from 'common/i18';

const PAYMENT_METHODS = {
  UPI: 'upi',
  CARD: 'card',
  EMANDATE: 'emandate',
};

const enableDisableMap = {
  1: 'enable',
  0: 'disable',
};

const isEnabled = (methodName) => (settings) => {
  const paymentMethod = findBy(settings.items, 'name', methodName) || {};
  return paymentMethod.setting_enabled === '1';
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

    const refConfigTagEnabled = !isConfigTagEnabled('subscription.emandate');

    const orgCode = org?.custom_code || 'rzp';
    const cardDescriptionText = cardDescription[orgCode] || cardDescription.rzp;
    const cardNoteText = cardNote[orgCode] || cardNote.rzp;
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
                            <Amount value={GATEWAY_MAX_LIMIT} hidePaisa />
                          </strong>
                          <br />
                          Payments above{' '}
                          <Amount value={rupeesToPaise(CARD_AFA_MAX_LIMIT)} hidePaisa /> will ask
                          the customer for OTP verification as well.
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
                              <Amount value={UPI_MAX_LIMIT_FOR_NON_BFSI} hidePaisa />
                            </strong>{' '}
                            (For BFSI: <Amount value={GATEWAY_MAX_LIMIT} hidePaisa />)
                            <br />
                            Payments above <Amount value={UPI_AFA_MAX_LIMIT} hidePaisa /> will ask
                            the customer for UPI PIN verification as well.
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
                              <Amount value={EMANDATE_MAX_LIMIT} hidePaisa />
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
