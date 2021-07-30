import { connect } from 'react-redux';
import { RZPFeatures } from 'merchant/helpers/data';
import { classList, findBy } from 'common/utils/rzp-utils';

import DocsLink from 'merchant/components/DocsLink';
import Amount from 'common/ui/Amount';
import Banner from 'common/ui/Banner';
import HeaderAction from 'common/ui/HeaderAction';
import SwitchField from 'common/ui/Forms/SwitchField';
import TakeATourButton from 'merchant/components/QuickGuide/TakeATourButton';
import Alert from 'common/ui/Forms/Alert';
import Spinner from 'common/ui/Spinner';

import { fetchSettings, saveSettings } from 'merchant/reducers/subscriptions';
import { showNotification } from 'merchant_common/reducers/notifications';

const PAYMENT_METHODS = {
  UPI: 'upi',
  CARD: 'card',
  EMANDATE: 'emandate',
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
    const { settings, user, org } = this.props;

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
            <TakeATourButton feature={RZPFeatures.SUBSCRIPTIONS} />

            <DocsLink url="https://razorpay.com/docs/subscriptions/" />
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
                      checked
                      title={
                        <>
                          <i class="i i-card m-r" /> Card
                        </>
                      }
                      onToggleChange={this.onToggleChange(PAYMENT_METHODS.CARD)}
                      checked={isEnabled(PAYMENT_METHODS.CARD)(settings)}
                      description="Accept recurring payments via cards for your subscriptions in any of our supported international currencies."
                      info={
                        <>
                          Cards currently support recurring payments upto <Amount value={500000} />.
                          Charges of higher value would automatically fail for domestic cards.
                        </>
                      }
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
                          Accept UPI payments on subscriptions when recurring charge is less than{' '}
                          <b>₹ 200000</b>. Only supports Indian currency.
                        </>
                      }
                      info={
                        <>
                          UPI only supports recurring payments upto <Amount value={20000000} />.
                          Subscription of higher values will not have UPI as a payment method during
                          checkout.
                        </>
                      }
                    />
                  </div>

                  {user.isEmandateOnSubscriptionEnabled && (
                    <div class="col-md-4">
                      <ToggleCard
                        isNew
                        title={
                          <>
                            <i class="i i-upi m-r" /> Emandate
                          </>
                        }
                        onToggleChange={this.onToggleChange(PAYMENT_METHODS.EMANDATE)}
                        checked={isEnabled(PAYMENT_METHODS.EMANDATE)(settings)}
                        description={
                          <>
                            Accept recurring payments via bank accounts (NetBanking) for your
                            subscriptions. Only supports Indian currency.
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

const ToggleCard = ({ isNew, title, checked, info = null, description, onToggleChange }) => {
  return (
    <div class="panel panel-default ToggleCard">
      <div class="panel-heading">
        <span class="title">{title}</span>
        {isNew && <span class="badge bg-success m-l">new</span>}

        <span class="pull-right toggler-btn">
          <SwitchField checked={checked} onChange={onToggleChange} type="prime" />
          <strong class={classList('m-l', checked ? 'text-primary' : 'text-faded')}>
            {checked ? 'Enabled' : 'Disabled'}
          </strong>
        </span>
      </div>

      <div class="panel-body">
        <div class="description">{description}</div>
        {info !== null && (
          <div class="m-t">
            <Banner>
              <i class="i i-info-outline m-r" />
              <div>{info}</div>
            </Banner>
          </div>
        )}
      </div>
    </div>
  );
};

const isEnabled = (methodName) => (settings) => {
  const paymentMethod = findBy(settings.items, 'name', methodName) || {};

  return paymentMethod.setting_enabled === '1';
};
