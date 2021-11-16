import React from 'react';
import { connect } from 'react-redux';
import { RZPFeatures } from 'merchant/helpers/data';
import { classList, findBy } from 'common/utils/rzp-utils';

import DocsLink, { DocLink } from 'merchant/components/DocsLink';
import Amount from 'common/ui/Amount';
import Banner from 'common/ui/Banner';
import HeaderAction from 'common/ui/HeaderAction';
import SwitchField from 'common/ui/Forms/SwitchField';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import Alert from 'common/ui/Forms/Alert';
import Spinner from 'common/ui/Spinner';

import { fetchSettings, saveSettings } from 'merchant/reducers/subscriptions';
import { showNotification } from 'merchant_common/reducers/notifications';
import analytics from '../analytics';

const PAYMENT_METHODS = {
  UPI: 'upi',
  CARD: 'card',
  EMANDATE: 'emandate',
};

const GATEWAY_MAX_LIMIT = 20000000;
const UPI_MAX_LIMIT = 10000000;
const EMANDATE_MAX_LIMIT = 100000000;
const enableDisableMap = {
  '1': 'enable',
  '0': 'disable',
};

const isEnabled = (methodName) => (settings) => {
  const paymentMethod = findBy(settings.items, 'name', methodName) || {};
  return paymentMethod.setting_enabled === '1';
};

@connect(
  (state) => ({
    settings: state.subscriptions.settings,
    user: state.session.user,
    org: state.session.org,
  }),
  { fetchSettings, saveSettings, showNotification },
)
export default class SubscriptionsSettings extends React.Component {
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

    return this.props
      .saveSettings(data)
      .then(() => {
        cb(true);

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
    const { settings, user } = this.props;

    if (settings.loading) {
      return (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    }

    return (
      <div class="Subscriptions--Settings content-wrapper">
        <HeaderAction>
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
          style={user.isEmandateOnSubscriptionEnabled ? {} : { width: '854px' }}
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
                      user.isEmandateOnSubscriptionEnabled ? 'col-md-4' : 'col-md-6',
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
                      description={() => (
                        <>
                          Accept recurring payments via debit & credit cards for your subscriptions
                          in any of our{' '}
                          <DocLink
                            className="inline-doc"
                            target="_blank"
                            href="https://razorpay.com/docs/payments/payments/international-payments/#supported-currencies"
                          >
                            supported international currencies.
                          </DocLink>
                        </>
                      )}
                      info={() => (
                        <>
                          Accept payments upto{' '}
                          <strong>
                            {' '}
                            <Amount value={GATEWAY_MAX_LIMIT} />
                          </strong>
                          <br />
                          Payments above ₹ 5000 will ask the customer for OTP verification as well.
                        </>
                      )}
                      note={() => (
                        <>
                          <strong>Note:</strong> Only limited cards are supported due to new payment
                          regulations by RBI.{' '}
                          <DocLink
                            className="inline-doc"
                            target="_blank"
                            href="https://razorpay.com/docs/subscriptions/bank-options/#card-networks"
                          >
                            View supported cards
                          </DocLink>
                        </>
                      )}
                    />
                  </div>

                  <div
                    class={classList(
                      user.isEmandateOnSubscriptionEnabled ? 'col-md-4' : 'col-md-6',
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
                          Accept recurring payments via UPI apps like PhonePe, Paytm & BHIM for your
                          subscriptions. Only supports Indian currency.
                        </>
                      }
                      info={() => (
                        <>
                          Accept payments upto{' '}
                          <strong>
                            {' '}
                            <Amount value={UPI_MAX_LIMIT} />
                          </strong>{' '}
                          (For BFSI: <Amount value={GATEWAY_MAX_LIMIT} />)
                          <br />
                          Payments above ₹ 5000 will ask the customer for UPI PIN verification as
                          well.
                        </>
                      )}
                    />
                  </div>

                  {user.isEmandateOnSubscriptionEnabled && (
                    <div class="col-md-4">
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
                              <Amount value={EMANDATE_MAX_LIMIT} />
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
