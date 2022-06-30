import { useEffect, useState } from 'react';
import { merchantFetch } from 'merchant/utils/ajax';
import { connect } from 'react-redux';
import { updateConfig } from 'merchant/reducers/config';
import { showNotification } from 'merchant_common/reducers/notifications';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import SwitchField from 'common/ui/Forms/SwitchField';
import { SMS_NOTIF } from './deeplink-constants';
import TextHighlighter from 'common/ui/TextHighlighter';
import { isOrgFeatureExist } from 'merchant/models/User';

function SmsNotification({ currentUser, showNotification }) {
  const [sms_optin, setSmsOptin] = useState(null);
  const hideRazorpayTextLink = isOrgFeatureExist('hide_razorpay_text_link');
  function fetchSmsOptin() {
    return merchantFetch({
      url: `settlements/sms_notification/status`,
    });
  }

  function updateSmsOptin(optin = false) {
    return merchantFetch({
      url: `settlements/sms_notification/toggle`,
      headers: {
        'Content-Type': 'application/json',
      },
      method: 'post',
      data: {
        enable: optin,
      },
    });
  }

  useEffect(() => {
    const fetchSmsState = async () => {
      try {
        const response = await fetchSmsOptin();
        setSmsOptin(response.data.enabled);
      } catch (e) {
        showNotification({
          type: 'error',
          message: 'Error in fetching SMS preferences',
        });
      }
    };
    fetchSmsState();
  }, []);

  const analytics = (action) => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Settings',
      eventAction: `${action} - SMS notifications`,
    });

    analyticsTrack({
      objectName: 'sms notifications',
      actionName: 'toggled',
      screen: 'settings',
      properties: {
        location: 'configuration',
        currentValue: action === 'Enable' ? 'Disable' : 'Enable',
        newValue: action,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  const toggleSmsNotification = (sms_optin_checked, cb) => {
    if (sms_optin_checked) {
      analytics('Enable');
    } else {
      analytics('Disable');
    }

    updateSmsOptin(sms_optin_checked)
      .then((response) => {
        cb(true);
        setSmsOptin(response.data.enabled);
        showNotification({
          type: 'success',
          message: 'Your SMS preference was saved',
        });

        analyticsTrack({
          objectName: 'sms notifications toggle',
          actionName: 'result',
          screen: 'settings',
          properties: {
            location: 'configuration',
            status: 'Success',
            currentValue: sms_optin_checked ? 'Disable' : 'Enable',
            newValue: sms_optin_checked ? 'Enable' : 'Disable',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      })
      .catch(({ errors }) => {
        if (errors) {
          cb(false);
          showNotification({
            type: 'error',
            message: errors,
          });
          analyticsTrack({
            objectName: 'sms notifications toggle',
            actionName: 'result',
            screen: 'settings',
            properties: {
              location: 'configuration',
              status: 'Failure',
              failureReason: errors[0],
              ...getCommonAnalyticsProperties(window.rzp_user),
            },
          });
        }
      });
  };

  return (
    <div className="panel panel-default">
      <div className="panel-heading">
        <span className="title">
          <TextHighlighter hashedWith={SMS_NOTIF}>SMS Notifications</TextHighlighter>
        </span>

        <span className="toggler-btn">
          <SwitchField
            checked={!!sms_optin}
            onChange={(_, cb) => toggleSmsNotification(!sms_optin, cb)}
            type="prime"
          />
          {sms_optin ? (
            <b className="text-primary">Enabled</b>
          ) : (
            <b className="text-faded">Disbaled</b>
          )}
        </span>
      </div>

      <div className="panel-body">
        <form className="form-horizontal">
          <div className="description">
            Receive notifications {hideRazorpayTextLink ? '' : 'from Razorpay'} via SMS on
            your&nbsp;
            <strong>+91 - {currentUser.contact_mobile}</strong>
          </div>
        </form>
      </div>
    </div>
  );
}

const mapStateToProps = (state) => ({
  currentUser: state.session.user,
});

export default connect(mapStateToProps, {
  updateConfig,
  showNotification,
  openModal,
  closeModal,
})(SmsNotification);
