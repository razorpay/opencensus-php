import React, { useEffect, useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { connect } from 'react-redux';

import { graphqlRequestMutation, graphqlRequest } from '@federated/apps/shell/graphql';
import {
  SmsNotificationStatusResponse,
  MutationSmsNotificationToggleArgs,
} from 'common/typings/graph-types';
import SwitchField from 'common/ui/Forms/SwitchField';
import LoaderDots from 'common/ui/LoaderDots';
import TextHighlighter from 'common/ui/TextHighlighter';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { isOrgFeatureExist } from 'merchant/models/User';
import {
  SMS_NOTIFICATION_STATUS_QUERY,
  SMS_NOTIFICATION_TOGGLE_MUTATION,
} from 'merchant/views/AccountAndSettings/NotificationSettings/queries';
import { showNotification } from 'merchant_common/reducers/notifications';

import { SMS_NOTIF } from './deeplink-constants';
import { getI18FormattedPhoneNumber } from 'merchant/components/Mask/Contact';

function SmsNotification({ currentUser, showNotification }) {
  const [isSmsOptin, setSmsOptin] = useState<boolean | undefined>(undefined);
  const isRazorpayTextLinkHidden = isOrgFeatureExist('hide_razorpay_text_link');

  const {
    data: smsNotificationStatusData,
    isFetching,
    status,
  } = useQuery<{ smsNotificationStatus: SmsNotificationStatusResponse }>({
    queryKey: ['sms-notification-status'],
    queryFn: () =>
      graphqlRequest({
        document: SMS_NOTIFICATION_STATUS_QUERY,
      }),
    refetchOnMount: 'always',
    staleTime: Infinity,
  });

  const { mutate, isLoading } = useMutation({
    mutationFn: async ({ variables }: { variables: MutationSmsNotificationToggleArgs }) => {
      const response = await graphqlRequestMutation({
        document: SMS_NOTIFICATION_TOGGLE_MUTATION,
        variables,
      });
      return response;
    },
  });

  const isSmsNotificationEnabled = smsNotificationStatusData?.smsNotificationStatus.isEnabled;

  useEffect(() => {
    /** - using React's useEffect in favour of react-query's onSuccess callback
     * because useQuery's onSuccess callbacks are goting to be deprecated in v5
     */

    if (status === 'success') {
      setSmsOptin(isSmsNotificationEnabled);
    }
    if (status === 'error') {
      showNotification({
        type: 'error',
        message: 'Error in fetching SMS preferences',
      });
    }
  }, [status, isSmsNotificationEnabled, setSmsOptin, showNotification]);

  const analytics = (action) => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Settings',
      eventAction: `${action} - SMS notifications`,
    });

    selfServeTrackInitiate({
      selfServeAction: `SMS Notifications  ${action === 'Enable' ? 'Enable' : 'Disable'}`,
      page: 'Config',
      screen: 'Settings',
    });

    analyticsTrack({
      objectName: 'sms notifications',
      actionName: 'toggled',
      screen: 'settings',
      properties: {
        location: 'configuration',
        currentValue: action === 'Enable' ? 'Enable' : 'Disable',
        newValue: action,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  const handleSmsToggleSuccess = (sms_optin_checked, cb) => {
    cb(true);
    setSmsOptin(sms_optin_checked);
    selfServeTrackSuccess({
      selfServeAction: `SMS Notifications  ${sms_optin_checked ? 'Enable' : 'Disable'}`,
      page: 'Config',
      screen: 'Settings',
    });
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
  };

  const handleSmsToggleFailure = ({ response }, cb) => {
    cb(false);
    showNotification({
      type: 'error',
      message: response?.errors[0]?.message,
    });
    analyticsTrack({
      objectName: 'sms notifications toggle',
      actionName: 'result',
      screen: 'settings',
      properties: {
        location: 'configuration',
        status: 'Failure',
        failureReason: response?.errors[0]?.message,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  const toggleSmsNotification = (sms_optin_checked: boolean, cb: (success: boolean) => void) => {
    if (sms_optin_checked) {
      analytics('Enable');
    } else {
      analytics('Disable');
    }

    mutate(
      {
        variables: {
          toggleValue: sms_optin_checked,
        },
      },
      {
        onSuccess: () => {
          handleSmsToggleSuccess(sms_optin_checked, cb);
        },
        onError: (errorResponse) => {
          handleSmsToggleFailure(errorResponse as any, cb);
        },
      },
    );
  };

  if (isFetching) return <LoaderDots />;

  return (
    <div className="panel panel-default">
      <div className="panel-heading">
        <span className="title">
          <TextHighlighter hashedWith={SMS_NOTIF}>SMS Notifications</TextHighlighter>
        </span>
        <span className="toggler-btn">
          <SwitchField
            checked={!!isSmsOptin}
            onChange={(_, cb) => toggleSmsNotification(!isSmsOptin, cb)}
            type="prime"
          />
          {isLoading ? (
            <b className="text-faded">Updating</b>
          ) : isSmsOptin ? (
            <b className="text-primary">Enabled</b>
          ) : (
            <b className="text-faded">Disabled</b>
          )}
        </span>
      </div>

      <div className="panel-body">
        <form className="form-horizontal">
          <div className="description">
            Receive notifications {isRazorpayTextLinkHidden ? '' : 'from Razorpay'} via SMS on
            your&nbsp;
            <strong>{getI18FormattedPhoneNumber(currentUser.contact_mobile) || '----'}</strong>
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
  showNotification,
})(SmsNotification);
