import React from 'react';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { withI18Service } from 'common/i18';
import Input from 'common/new-ui/Input';
import DocsLink from 'merchant/components/DocsLink';
import ShowWhen from 'merchant/components/ShowWhen';
import track from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/track';
import { Alert } from '@razorpay/blade/components';
import { getWhatsPLNotificationStatus } from 'merchant/views/PaymentLinks/PaymentLinks/CreateV2/Utils/whatsAppUtils';
import { withRouter } from 'common/deprecated/withRouter';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { withSplitzService } from 'common/splitz';
import { whatsappAccountSetupAnalyticsTrack } from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/WhatsappSetup/utils';
import { showNotification } from 'merchant_common/reducers/notifications';

class Notify extends React.Component {
  handleEmailNotify = (event) => {
    const isChecked = !!event.target.value == '1';
    if (!isChecked) return;

    document.querySelector('[name=email]').focus();

    track.lj.fields.notifyEmail();
    track.segment.fields.notifyEmail();
  };

  handleSmsNotify = (event) => {
    const isChecked = !!event.target.value == '1';
    if (!isChecked) return;

    document.querySelector('[name=contact]').focus();

    track.lj.fields.notifySms();
    track.segment.fields.notifySms();
  };

  handleNotifyClick = () => {
    const { user, showNotification } = this.props;
    if (user.isOwner) {
      this.props.history.push(ROUTES_INFO.WHATSAPP_ACCOUNT_SETUP);
    } else {
      showNotification({
        type: 'error',
        message: 'Can be updated from Owner account only',
      });
    }
  };

  shouldShowSmsDisabledMessage = ({ user, defaultContactValue }) => {
    return user.isPlV2DisableAllSmsEnabled && defaultContactValue === '1';
  };

  shouldShowEmailDisabledMessage = ({ user, defaultEmailValue }) => {
    return user.isPlV2DisableAllEmailEnabled && defaultEmailValue === '1';
  };

  render() {
    const { props } = this;
    const { user, splitz, featureStatus, defaultEmailValue, defaultContactValue, disabled } = props;
    const { isFeatureLoading, isNotificationShow, title, CtaText, businessProviderName } =
      getWhatsPLNotificationStatus({
        user,
        splitz,
        featureStatus,
      });

    return (
      <>
        <Input.Group
          className={'InputGroup--inline InputGroup--near customer-notify hidden-xs'}
          disabled={disabled}
        >
          <div className={'Input-content'}>
            <Input.Check
              autoRender
              name="email_notify"
              fieldLabel="Notify via Email"
              onClick={this.handleEmailNotify}
              defaultValue={defaultEmailValue}
            />
            <Input.Check
              autoRender
              name="sms_notify"
              fieldLabel="Notify via SMS"
              onClick={this.handleSmsNotify}
              defaultValue={defaultContactValue}
            />
          </div>
        </Input.Group>
        {(this.shouldShowEmailDisabledMessage({ user, defaultEmailValue }) ||
          this.shouldShowSmsDisabledMessage({ user, defaultContactValue })) && (
          <Input.Group className={'InputGroup InputGroup--near hidden-xs'}>
            <div className={'Input-content'}>
              <span style={{ color: 'red', display: 'inline-block', width: '50%' }}>
                {this.shouldShowEmailDisabledMessage({ user, defaultEmailValue }) && (
                  <small>Email disabled. Contact support to enable.</small>
                )}
              </span>
              <span style={{ color: 'red', display: 'inline-block', width: '50%' }}>
                {this.shouldShowSmsDisabledMessage({ user, defaultContactValue }) && (
                  <small>SMS disabled. Contact support to enable.</small>
                )}
              </span>
            </div>
          </Input.Group>
        )}
        <Input.Group className={'InputGroup--inline InputGroup--near hidden-xs'}>
          <div className={'Input-content'}>
            {
              <ShowWhen
                additionalCondition={() =>
                  !this.props.i18.isConfigTagEnabled('app_store.app_store') &&
                  !this.props.i18.isConfigTagEnabled('documentation.documentation')
                }
              >
                <DocsLink
                  title="More ways to notify"
                  url="https://razorpay.com/app-store/"
                  style={{ paddingLeft: '0' }}
                />
              </ShowWhen>
            }
          </div>
        </Input.Group>
        {!isFeatureLoading && isNotificationShow ? (
          <Alert
            title={title}
            marginTop="spacing.4"
            isDismissible={false}
            actions={{
              primary: {
                onClick: () => {
                  whatsappAccountSetupAnalyticsTrack({
                    objectName: `WA Notification ${CtaText} PL Screen`,
                    actionName: 'Clicked',
                    screen: 'Create Payment link',
                    properties: {
                      selectedBusinessAccount: businessProviderName,
                    },
                  });
                  this.handleNotifyClick();
                },
                text: CtaText,
              },
            }}
            color="information"
          />
        ) : null}
      </>
    );
  }
}

export default compose(
  withSplitzService,
  withRouter,
  withI18Service,
  connect((state) => ({ user: state.session.user }), {
    showNotification,
  }),
)(Notify);
