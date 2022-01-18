import { useEffect, useState, useRef } from 'react';
import { merchantFetch } from 'merchant/utils/ajax';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import SwitchField from 'common/ui/Forms/SwitchField';
import RTracking from 'react-tracking';
import { WHATSAPP_NOTIF } from './deeplink-constants';
import TextHighlighter from 'common/ui/TextHighlighter';
import { fetchFeatureStatus as fnFetchFeatureStatus } from 'merchant/reducers/config';

function WhatsappNotification({
  currentUser,
  showNotification,
  fetchFeatureStatus,
  location,
  history,
  tracking,
  org,
}) {
  const [whatsapp_optin, setWhatsappOptin] = useState(null);
  const whatsappEnableSection = useRef(null);
  const [isWhatsAppOptPending, setIsWhatsAppOptPending] = useState(false);
  const [isWhatsappOrg, setWhatsappOrg] = useState(false);
  const [isWhatsappMid, setWhatsappMid] = useState(false);

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
      data,
    });
  }

  function updateWhatsappOptin(optin = false, data = { source: 'pg.settings.config' }) {
    return merchantFetch({
      url: `users/whatsapp/${optin ? 'opt_in' : 'opt_out'}`,
      method: 'post',
      data,
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

    // check paypal org feature
    if (org.features.indexOf('axis_whatsapp') > -1) {
      setWhatsappOrg(true);
      // check paypal MID feature
      fetchFeatureStatus(currentUser.id, 'axis_whatsapp_enable')
        .then((fetchFeatureStatusResp) => {
          if (fetchFeatureStatusResp?.data?.status) {
            setWhatsappMid(true);
          }
        })
        .catch((err) => {
          if (err) {
            showNotification({
              type: 'error',
              message: err.errors[0],
            });
          }
        });
    }
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
    /*
      Added the success check on the response data object because if the API is
      getting timed out atleast the we will be able to properly communicate it 
      to the user without throwing errror
    */
    updateWhatsappOptin(whatsapp_optin_checked)
      .then((data) => {
        if (data?.data?.optin_status || data?.data?.length === 0) {
          cb(true);
          setWhatsappOptin(whatsapp_optin_checked);
          showNotification({
            type: 'success',
            message: 'Your preference was saved',
          });
        } else {
          cb(false);
          setIsWhatsAppOptPending(true);
        }
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
    <div className="panel panel-default whatsapp-notification" ref={whatsappEnableSection}>
      <div className="panel-heading">
        <span className="title">
          <TextHighlighter hashedWith={WHATSAPP_NOTIF}>WhatsApp Notifications</TextHighlighter>
        </span>
        {isWhatsAppOptPending ? (
          <div className="optin-pending-label">PENDING</div>
        ) : (
          (!isWhatsappOrg || (isWhatsappOrg && isWhatsappMid)) && (
            <span className="toggler-btn">
              <SwitchField
                checked={!!whatsapp_optin}
                onChange={(_, cb) => toggleWhatsappNotification(!whatsapp_optin, cb)}
                type="prime"
              />
              {whatsapp_optin ? (
                <b className="text-primary">Enabled</b>
              ) : (
                <b className="text-faded">Disabled</b>
              )}
            </span>
          )
        )}
      </div>

      <div className="panel-body">
        <form className="form-horizontal">
          {isWhatsAppOptPending && (
            <div className="optin-pending-description">
              We have received your request. You will receive a text on WhatsApp when your request
              is processed.
            </div>
          )}
          <div className="description">
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
  org: state.session.org,
});
export default withRouter(
  connect(mapStateToProps, {
    showNotification: fnShowNotification,
    fetchFeatureStatus: fnFetchFeatureStatus,
  })(
    // eslint-disable-next-line babel/new-cap
    RTracking(() => {
      window.rzpQ.component('WhatsappNotification');
    })(WhatsappNotification),
  ),
);
