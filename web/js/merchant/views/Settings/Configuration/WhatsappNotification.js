import { useEffect, useState, useRef } from 'react';
import { merchantFetch } from 'merchant/utils/ajax';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import { showNotification } from 'merchant_common/reducers/notifications';
import SwitchField from 'common/ui/Forms/SwitchField';
import RTracking from 'react-tracking';
import { WHATSAPP_NOTIF } from './deeplink-constants';
import TextHighlighter from 'common/ui/TextHighlighter';

function WhatsappNotification({ currentUser, showNotification, location, history, tracking }) {
  const [whatsapp_optin, setWhatsappOptin] = useState(null);
  const whatsappEnableSection = useRef(null);

  if (location.hash.startsWith('#whatsapp_enable') && whatsappEnableSection.current) {
    window.rzpAnalytics({
      eventCategory: 'Whatsapp Enable',
      eventAction: `Clicked ${location.hash}`,
      eventLabel: `Home`,
    });
    tracking.trackEvent(
      window.rzpQ.onbr().initiated(`whatsapp.${location.hash.replace('#', '')}`, {
        clickSource: 'Announcement Banner',
      }),
    );
    whatsappEnableSection.current.scrollIntoView();
    history.push({
      pathname: history.location.pathname,
      hash: '',
    });
  }

  function fetchWhatsappOptin(data = { source: 'pg.settings.config' }) {
    return merchantFetch({
      url: `users/whatsapp/opt_in_status`,
      method: 'get',
      data: data,
    });
  }

  function updateWhatsappOptin(optin = false, data = { source: 'pg.settings.config' }) {
    return merchantFetch({
      url: `users/whatsapp/${optin ? 'opt_in' : 'opt_out'}`,
      method: 'post',
      data: data,
    });
  }

  useEffect(() => {
    const fetchWhatsappState = async () => {
      try {
        const response = await fetchWhatsappOptin();
        if (response && response.data && response.data.consent_status)
          setWhatsappOptin(response.data.consent_status);
        // to be taken from api response
        else {
          setWhatsappOptin(false);
        }
      } catch (e) {
        setWhatsappOptin(false);
      }
    };
    fetchWhatsappState();
  }, []);

  const analytics = (action) => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settings',
      eventAction: `${action} - WhatsApp notifications`,
    });
  };

  const toggleWhatsappNotification = (whatsapp_optin_checked, cb) => {
    if (whatsapp_optin_checked) {
      analytics('Enable');
    } else {
      analytics('Disable');
    }

    updateWhatsappOptin(whatsapp_optin_checked)
      .then((r) => {
        cb(true);
        setWhatsappOptin(whatsapp_optin_checked);
        showNotification({
          type: 'success',
          message: 'Your preference was saved',
        });
      })
      .catch(({ errors }) => {
        if (errors) {
          cb(false);
          showNotification({
            type: 'error',
            message: errors,
          });
        }
      });
  };

  return (
    <div class="panel panel-default" ref={whatsappEnableSection}>
      <div class="panel-heading">
        <span class="title">
          <TextHighlighter hashedWith={WHATSAPP_NOTIF}>WhatsApp Notifications</TextHighlighter>
        </span>

        <span class="toggler-btn">
          <SwitchField
            checked={whatsapp_optin ? true : false}
            onChange={(_, cb) => toggleWhatsappNotification(whatsapp_optin ? false : true, cb)}
            type="prime"
          />
          {whatsapp_optin ? (
            <b class="text-primary">Enabled</b>
          ) : (
            <b class="text-faded">Disabled</b>
          )}
        </span>
      </div>

      <div class="panel-body">
        <form class="form-horizontal">
          <div class="description">
            Receive notifications from Razorpay via WhatsApp{' '}
            {currentUser.user && currentUser.user.contact_mobile && (
              <span>
                on your number <strong>+91 - {currentUser.user.contact_mobile}</strong>
              </span>
            )}
          </div>
        </form>
      </div>
    </div>
  );
}

const mapStateToProps = (state) => ({
  currentUser: state.session.user,
});
export default withRouter(
  connect(mapStateToProps, {
    showNotification,
  })(
    RTracking(() => {
      window.rzpQ.component('WhatsappNotification');
    })(WhatsappNotification),
  ),
);
