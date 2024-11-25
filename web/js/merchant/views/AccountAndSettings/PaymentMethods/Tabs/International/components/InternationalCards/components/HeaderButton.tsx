import { Button } from '@razorpay/blade/components';
import { ShowNotificationType, Store, UpdateSessionType, User as UserType } from 'common/typings';
import SwitchField from 'common/ui/Forms/SwitchField';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import User from 'merchant/models/User';
import { updateSession as updateSessionAction } from 'merchant/reducers/session';
import { merchantFetch } from 'merchant/utils/ajax';
import { CommonICProductsState } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import { showNotification as showNotificationAction } from 'merchant_common/reducers/notifications';
import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { trackIEEvent } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils/track';
import InternationalCardRequestContainer from './InternationalCardRequestContainer';

type Props = CommonICProductsState & {
  showNotification: ShowNotificationType;
  updateSession: UpdateSessionType;
  openQuestionnaire: () => void;
  user: UserType;
  isRequestRejectedFor90Days: boolean;
  isMobileDevice: boolean;
};

const HeaderButton = ({
  showNotification,
  updateSession,
  isAnyProductApproved,
  isAnyProductRequested,
  isAnyProductRejected,
  openQuestionnaire,
  user,
  isRequestRejectedFor90Days,
  isMobileDevice,
}: Props): JSX.Element | null => {
  const showInternationalCardRequest: boolean = user.isOrgRZP || user.isOrgCurlec;

  const toggleInternationalization = (enableInternational, postActionCB) => {
    selfServeTrackInitiate({
      selfServeAction: 'International Payments Applied',
      page: 'Config',
      screen: 'Settings',
    });

    trackIEEvent({
      objectName: 'International Cards Toggle',
      actionName: 'Clicked',
      properties: {
        toggle_status: enableInternational ? 'enabled' : 'disabled',
      },
    });

    return merchantFetch({
      url: 'merchant/international',
      method: 'PATCH',
      data: {
        international: enableInternational ? 1 : 0,
      },
    })
      .then((resp) => {
        // Check if the response sets international as intended in this request
        if (resp.data.international === !!enableInternational) {
          selfServeTrackSuccess({
            selfServeAction: 'International Payments Applied',
            page: 'Config',
            screen: 'Settings',
          });
          postActionCB(true);
          // Update user in store
          const _user = new User({
            ...user,
            international: resp.data.international,
          });

          updateSession({ user: _user });
        } else {
          throw new Error(
            'We are unable to process this request. Please reach out to support@razorpay.com',
          ); // This code is ideally unreachable as per business logic. However, since Api silently fails here, hence handling explicitly.
        }
      })
      .catch((err) => {
        postActionCB(false);
        let error = 'Something went wrong!';

        if (typeof err === 'object' && err.hasOwnProperty('message')) {
          error = err.message;
        } else if (err.errors) {
          error = err.errors[1];
        }

        showNotification({
          type: 'error',
          message: error,
        });
      });
  };

  const isInternationalEnabled = !!user?.international;

  if (isAnyProductApproved) {
    return (
      <span className="toggler-btn" style={{ marginLeft: '10px' }}>
        <SwitchField
          defaultChecked={isInternationalEnabled}
          onChange={toggleInternationalization}
          type="prime"
        />
        {isInternationalEnabled ? (
          <b className="text-primary">Enabled</b>
        ) : (
          <b className="text-faded">Disabled</b>
        )}
      </span>
    );
  } else if (!isAnyProductRequested || (isAnyProductRejected && !isRequestRejectedFor90Days)) {
    return (
      <InternationalCardRequestContainer
        showInternationalCardRequest={showInternationalCardRequest}
      >
        <Button
          size={isMobileDevice ? 'medium' : 'small'}
          onClick={() => {
            trackIEEvent({
              objectName: 'Request For International Cards',
              actionName: 'Clicked',
            });
            openQuestionnaire();
          }}
          isDisabled={!showInternationalCardRequest}
        >
          Request for international cards
        </Button>
      </InternationalCardRequestContainer>
    );
  }

  return null;
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    { showNotification: showNotificationAction, updateSession: updateSessionAction },
    dispatch,
  );
};

const mapStateToProps = (state: Store) => ({
  user: state.session.user,
  isMobileDevice: state.app.isMobileResolution,
});

export default connect(mapStateToProps, mapDispatchToProps)(HeaderButton);
