import { connect } from 'react-redux';
import PropTypes from 'prop-types';
import React from 'react';
import HeaderAction from 'common/ui/HeaderAction';
import ModalHeader from 'common/ui/ModalHeader';
import ShowWhen from 'merchant/components/ShowWhen';
import DocsLink from 'merchant/components/DocsLink';

import { sendInvitation } from 'merchant/reducers/invitation';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

import PendingInvitationsList from './PendingInvitations/List';
import TeamMembersList from './TeamMembers/List';
import Merchant2FASettings from './components/Merchant2FASettings';
import NewInvitation from './components/NewInvitation';

import rolesList from 'merchant/helpers/permissions/roles-list';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

class ManageTeamContainer extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  inviteNewMember = () => {
    analyticsTrack({
      objectName: 'invite new member',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        location: 'manage team',
        pendingInvitations: this.props.user.invitations.length,
        // members left to be added
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    const visibleFields = {
      email: true,
      role: true,
    };

    const defaults = {
      sender_name: this.props.user.name,
      role: rolesList.MANAGER,
    };
    this.props.openModal({
      size: 'small',
      component: (
        <>
          <ModalHeader title="Invite New Member" onCloseClick={this.props.closeModal} />
          <div className="modal-body">
            <NewInvitation
              visibleFields={visibleFields}
              defaults={defaults}
              onSuccess={this.props.closeModal}
              onFormSubmit={this.props.sendInvitation}
              successMsg={(data) => `Invitation has been successfully sent to ${data.email}`}
              ctaText="Send Invitation"
            />
          </div>
        </>
      ),
    });
  };

  onDocumentationClick = () => {
    analyticsTrack({
      objectName: 'documentation',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        location: 'manage team',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  componentDidMount() {
    analyticsTrack({
      objectName: 'manage team',
      actionName: 'viewed',
      screen: 'my account',
      properties: {
        location: 'manage team',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  }
  render() {
    const { user } = this.props;

    return (
      <div className="content-wrapper content-sm" id="settings-content">
        {/* passing the new props to the HeaderAction component to support the m-web view */}
        <HeaderAction responsive>
          <div className="btn-toolbar pull-right">
            <DocsLink
              url="https://razorpay.com/docs/team-support/"
              onClick={this.onDocumentationClick}
            />
            <ShowWhen additionalCondition={(userCurrent) => userCurrent.isAllowedEdit('team')}>
              {/* To make the CTAs on header to be sticky in teh bottom need to add a wrapper to them added same */}
              <span className="cta-container">
                <button className="btn btn-primary" onClick={this.inviteNewMember}>
                  Invite New Member
                </button>
              </span>
            </ShowWhen>
          </div>
        </HeaderAction>
        {!user.org_enforced_second_factor_auth && (
          <ShowWhen myRole="owner">
            <Merchant2FASettings />
          </ShowWhen>
        )}
        <div className="ManageTeam--list">
          <ShowWhen additionalCondition={(userCurrent) => userCurrent.isAllowedView('invitations')}>
            <PendingInvitationsList {...this.props} />
          </ShowWhen>

          <div className="m-t" />

          <TeamMembersList {...this.props} />
        </div>
      </div>
    );
  }
}

const mapStateToProps = (state) => ({
  user: state.session.user.user,
});

export default connect(mapStateToProps, { sendInvitation, openModal, closeModal })(
  ManageTeamContainer,
);
