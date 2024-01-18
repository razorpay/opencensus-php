import React, { useState, useEffect } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'common/ui/Forms/InputField';
import { useI18Service } from 'common/i18';
import { showNotification } from 'merchant_common/reducers/notifications';
import { required, isEmail } from 'common/utils/validators';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { EMAIL_NOTIF } from './deeplink-constants';
import TextHighlighter from 'common/ui/TextHighlighter';
import { triggerOtpOnEmail } from 'merchant_common/reducers/twoFactor';
import TwoFactorVerificationOTP from 'common/ui/TwoFactorVerification/TwoFactorVerificationOTP';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { updateEmailSettings } from 'merchant/reducers/config';
import { updateEmailSettingsInStore } from 'merchant/reducers/graphql/configSettings/actions';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { useMutation } from '@tanstack/react-query';
import { graphqlRequestMutation } from 'common/services/graphql/graphql-client';
import { NOTIFICATION_EMAIL_UPDATE_MUTATION } from 'merchant/views/AccountAndSettings/NotificationSettings/queries';
import { MutationNotificationEmailUpdateArgs } from 'common/typings/graph-types';
import EmailNotificationProps from 'merchant/views/Settings/Configuration/typings/EmailNotification';

const EmailNotifications: React.FunctionComponent<EmailNotificationProps> = (props) => {
  const [token, setToken] = useState('');
  const [transactionReportEmail, setTransactionReportEmail] = useState('');
  const i18Service = useI18Service();

  const { mutateAsync: updateNotificationEmails } = useMutation({
    mutationFn: async ({ variables }: { variables: MutationNotificationEmailUpdateArgs }) => {
      const response = await graphqlRequestMutation({
        document: NOTIFICATION_EMAIL_UPDATE_MUTATION,
        variables: {
          transactionReportEmail: variables.transactionReportEmail,
        },
      });
      return response;
    },
  });

  useEffect(() => {
    props.initialize(props.config);
  }, [props.initialize, props.config]);

  const onEmailOtpConfirm = (data) => {
    const obj = {
      ...data,
      token,
      transaction_report_email: transactionReportEmail,
    };
    // `updateEmailSettings` does not have corredponding mutation on GQL, hence keeping it as it is.
    return props.updateEmailSettings(obj).then((_) => {
      props.showNotification({
        type: 'success',
        message: 'Emails Updated',
        hidePrevious: true,
      });
    });
  };

  const triggerVerificationOtp = () => {
    return triggerOtpOnEmail()
      .then(({ data }) => {
        setToken(data.token);
        // eslint-disable-next-line @typescript-eslint/no-use-before-define
        triggerOtpModal();
      })
      .catch(({ err }) => {
        props.showNotification({
          type: 'error',
          message: err.errors[0],
        });
      });
  };

  const triggerOtpModal = () => {
    props.openModal({
      size: 'small',
      component: (
        <TwoFactorVerificationOTP
          onSuccess={props.closeModal}
          onConfirm={onEmailOtpConfirm}
          onClose={props.closeModal}
          onResend={triggerVerificationOtp}
          title="OTP Verification"
          renderMessage={() => (
            <p className="m-b">
              The action you are trying to perform needs 2 step verification. An Email with 6-digit
              OTP has been sent to {props?.user?.user?.email}.
            </p>
          )}
        />
      ),
    });
  };

  const selfServeAnalytics = (action) => {
    return {
      selfServeAction: action,
      page: 'Config',
      screen: 'Settings',
    };
  };

  const handleUpdate = (data) => {
    if (props.org.features.indexOf('email_update_2fa_enabled') > -1) {
      // `updateEmailSettings` method does not have any corresponding schema on GQL, hence keeping as it is.
      return props
        .updateEmailSettings(data)
        .then((_) => {
          selfServeTrackSuccess(selfServeAnalytics('Email Notification Enabled'));
          props.showNotification({
            type: 'success',
            message: 'Emails Updated',
            hidePrevious: true,
          });
        })
        .catch((err) => {
          const error = err.errors;
          if (
            typeof error === 'object' &&
            !!error.internal_error_code &&
            error.internal_error_code === 'BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED'
          ) {
            return triggerVerificationOtp();
          }
          return props.showNotification({
            type: 'error',
            message: err.errors[0],
          });
        });
    } else {
      return updateNotificationEmails(
        {
          variables: {
            transactionReportEmail: data.transaction_report_email,
          },
        },
        {
          onSuccess: ({ notificationEmailUpdate }) => {
            if (notificationEmailUpdate.__typename === 'NotificationEmailUpdateSuccessResponse') {
              //dispatch action here to sync the redux store with updated email addresses.
              props.updateEmailSettingsInStore(data);

              selfServeTrackSuccess(selfServeAnalytics('Email Notification Enabled'));
              props.showNotification({
                type: 'success',
                message: 'Emails Updated',
                hidePrevious: true,
              });
            }
          },
        },
      );
    }
  };

  const handleSubmit = ({ transaction_report_email }) => {
    const emails = transaction_report_email ? transaction_report_email.split(',') : [];

    const validEmails: string[] = [];
    const invalidEmails: string[] = [];

    emails.forEach((emailString: string) => {
      const trimmedEmail = emailString.trim();
      if (isEmail(trimmedEmail)) {
        validEmails.push(trimmedEmail);
      } else {
        invalidEmails.push(trimmedEmail);
      }
    });

    if (invalidEmails.length > 0) {
      props.showNotification({
        type: 'error',
        message: `The provided transaction report email is invalid: ${invalidEmails.join(',')}`,
      });
    }

    setTransactionReportEmail(validEmails.join(','));

    selfServeTrackInitiate(selfServeAnalytics('Email Notification Enabled'));

    return handleUpdate({
      transaction_report_email: validEmails,
    });
  };

  const analytics = () => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Settings',
      eventAction: 'Change - Email Notifications Addresses',
    });
    analyticsTrack({
      objectName: 'save email notifications',
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        location: 'configuration',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  const onSave = (e) => {
    analytics();
    return props.handleSubmit(handleSubmit)(e);
  };

  const { user } = props;
  const description = `Enter email addresses that will receive email notifications regarding payments,${
    i18Service.isConfigTagEnabled('settlements.settlement') ? '' : 'settlements, '
  }daily payment reports, webhooks, etc. (You can enter multiple email addresses separated by a comma.)`;

  return (
    <div>
      {[rolesList.OWNER, rolesList.ADMIN].includes(user.role) && (
        <div className="panel panel-default ftx-parent">
          <div className="panel-heading">
            <span className="title">
              <TextHighlighter hashedWith={EMAIL_NOTIF}>Email Notifications</TextHighlighter>
            </span>
          </div>

          <div className="panel-body">
            <form className="form-horizontal" onSubmit={onSave}>
              <div className="description">{description}</div>

              <div className="form-group">
                <div className="col-sm-10">
                  <Field
                    name="transaction_report_email"
                    component={InputField}
                    className="form-control"
                    maxLength="255"
                    validate={required()}
                  />
                </div>

                <div className="col-sm-2">
                  <AsyncButton
                    className="btn btn-primary email-notification-cta"
                    text="Save Changes"
                    pendingText="Saving..."
                    onClick={onSave}
                  />
                </div>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};

export default compose<React.ComponentType>(
  connect(
    (state) => ({
      user: state.session.user,
      config: state.config.config,
      org: state.session.org,
    }),
    { showNotification, updateEmailSettings, openModal, closeModal, updateEmailSettingsInStore },
  ),
  reduxForm({
    form: 'configForm',
  }),
)(EmailNotifications);
