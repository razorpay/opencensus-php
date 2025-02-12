import { Text } from '@razorpay/blade/components';
import PropTypes from 'prop-types';
import AsyncButton from 'react-async-button';
import { connect } from 'react-redux';

import { withRouter } from 'common/deprecated/withRouter';
import DataTable from 'common/ui/Table/DataTable';
import { role } from 'common/ui/item/pair';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { getI18FormattedPhoneNumber } from 'merchant/components/Mask/Contact';
import { isJKOfflineMerchant } from 'merchant/components/Sidebar/helpers';
import ListContainer from 'merchant/containers/ListContainer';
import {
  fetchTeam as fetchAll,
  unlockMember as unlockMemberReducer,
  unverifyContact as unverifyContactReducer,
} from 'merchant/reducers/team';
import * as NotificationActions from 'merchant_common/reducers/notifications';

import Actions from './Actions';
import JKTeamMembers from '../components/JKTeamMembers';

class MembersListContainer extends ListContainer {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  actions = {
    title: '',
    columnClass: 'text-right',
    value: (member, isJkOrg = false) => (
      <Actions
        member={member}
        items={this.props.items}
        isJkOrg={isJkOrg}
        isEmailSelfServeEnabled={this.props.user.isEmailSelfServeEnabled}
      />
    ),
  };

  member = {
    title: 'Member',
    value: (member) => (
      <>
        {member.name && <p>{member.name}</p>}
        <p className="text-muted no-margin">{member.email}</p>
        {member.account_locked && (
          <AccountLocked
            unlockMember={this.props.unlockMember}
            memberId={member.id}
            showNotification={this.props.showNotification}
          />
        )}
      </>
    ),
  };

  contactPhone = {
    title: 'Phone Number',
    value: (member, isJkORg) => (
      <>
        {isJkORg ? (
          <Text color="surface.text.gray.muted" weight="regular" size="small">
            {getI18FormattedPhoneNumber(member.contact_mobile) || '--'}
          </Text>
        ) : (
          <p>{getI18FormattedPhoneNumber(member.contact_mobile) || '--'}</p>
        )}
        {!member.org_enforced_second_factor_auth &&
          member.id !== this.props.currentUser.id &&
          !!member.contact_mobile &&
          member.contact_mobile_verified && (
            <RaiseContactMobileLost
              memberId={member.id}
              memberEmail={member.email}
              unverifyContact={this.props.unverifyContact}
              showNotification={this.props.showNotification}
              confirm={this.context.confirm}
            />
          )}
      </>
    ),
  };

  render() {
    const { items, loading, org, isMobile, user } = this.props;
    const isJkORg = isJKOfflineMerchant(org, user);

    if (isJkORg && isMobile) {
      return (
        <JKTeamMembers items={items} actions={this.actions} contactPhone={this.contactPhone} />
      );
    }

    return (
      <DataTable
        title="Members"
        panelHeading={
          !loading && {
            title: (
              <>
                Team Members (<small className="text-muted">{items.length}</small>)
              </>
            ),
          }
        }
        columns={[
          { ...this.member, width: '4fr' },
          { ...this.contactPhone, width: '2fr' },
          { ...role, width: '1fr' },
          { ...this.actions, width: '1fr' },
        ]}
        progressLoader={true}
        {...this.props}
      />
    );
  }
}

function AccountLocked({ unlockMember, memberId, showNotification }) {
  const unlock = () => {
    return unlockMember(memberId)
      .then(() => {
        showNotification({
          type: 'success',
          message: 'Team member account is successfully unlocked',
        });
      })
      .catch(({ errors }) => {
        showNotification({
          type: 'error',
          message: (errors || [])[0],
        });
      });
  };

  return (
    <span className="status-label label-pale-warning m-t">
      <i className="i i-info-circle text-warning" /> Account blocked due to multiple wrong login
      attempts{' '}
      <AsyncButton
        text="Unlock"
        pendingText="Unlocking"
        onClick={unlock}
        className="btn-link text-warning"
      />
    </span>
  );
}

function RaiseContactMobileLost({
  memberId,
  memberEmail,
  unverifyContact,
  showNotification,
  memberRole,
  noOfTeamMembers,
  confirm,
}) {
  const unverify = () => {
    analyticsTrack({
      objectName: 'Invalidate 2fa popup',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        action: 'confirm',
        location: 'manage team',
        role: memberRole,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    return unverifyContact(memberId)
      .then(() => {
        analyticsTrack({
          objectName: 'invalidate 2fa',
          actionName: 'status',
          screen: 'my account',
          properties: {
            status: 'success',
            location: 'manage team',
            role: memberRole,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        showNotification({
          type: 'success',
          message: '2FA is successfully invalidated for user account',
        });
      })
      .catch(({ errors }) => {
        analyticsTrack({
          objectName: 'invalidate 2fa',
          actionName: 'status',
          screen: 'my account',
          properties: {
            status: 'failure',
            failureReason: errors.errors[0],
            location: 'manage team',
            role: memberRole,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        showNotification({
          type: 'error',
          message: (errors || [])[0],
        });
      });
  };

  const unverifyAfterConfirm = () => {
    analyticsTrack({
      objectName: 'Invalidate 2fa',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        status: 'success',
        role: memberRole,
        noOfTeamMembers,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    confirm({
      header: 'Invalidate 2FA',
      affirmativeLabel: 'Confirm',
      message: <>This will invalidate 2FA for the user {memberEmail}. Click confirm to continue</>,
      action: unverify,

      abort: () => {
        analyticsTrack({
          objectName: 'Invalidate 2fa popup',
          actionName: 'clicked',
          screen: 'my account',
          properties: {
            action: 'cancel',
            location: 'manage team',
            role: memberRole,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      },
    });
  };

  return (
    <span className="small">
      <AsyncButton
        onClick={unverifyAfterConfirm}
        className="btn-link no-padding"
        pendingText="Invalidating..."
      >
        Invalidate 2FA
      </AsyncButton>
    </span>
  );
}

const mapStateToProps = (state) => ({
  user: state.session.user,
  currentUser: state.session.user.user,
  org: state.session.org,
  isMobile: state.app.isMobileResolution,
  ...state.team,
});

export default connect(mapStateToProps, {
  fetchAll,
  unlockMember: unlockMemberReducer,
  unverifyContact: unverifyContactReducer,
  ...NotificationActions,
})(withRouter(MembersListContainer));
