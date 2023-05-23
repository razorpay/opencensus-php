import { connect } from 'react-redux';
import PropTypes from 'prop-types';
import React from 'react';
// eslint-disable-next-line no-restricted-imports
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
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import Styled from 'styled-components';

const StyledDiv = Styled.div`
  margin-top:${({ isFlowRevamped }) => (isFlowRevamped ? '40px' : '0px')};
`;

class ManageTeamContainer extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  inviteNewMember = () => {
    selfServeTrackInitiate({
      selfServeAction: 'New Member Invited',
      page: 'Team',
      screen: 'My Account',
    });
    analyticsTrack({
      objectName: 'invite new member',
      actionName: 'clicked',
      screen: 'my account',
      properties: {
        location: 'manage team',
        pendingInvitations: this.props.user?.user?.invitations?.length,
        // members left to be added
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    const visibleFields = {
      email: true,
      role: true,
    };

    const defaults = {
      sender_name: this.props.user?.user?.name,
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
        <HeaderAction
          responsive
          target={
            user.isAccountAndSettingsRevampEnabled ? 'main > .content' : 'tabbed-container > header'
          }
        >
          <div className="btn-toolbar pull-right">
            <ShowWhen
              additionalCondition={(user) =>
                !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Documentation)
              }
            >
              <DocsLink
                url="https://razorpay.com/docs/team-support/"
                onClick={this.onDocumentationClick}
              />
            </ShowWhen>

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

        <ShowWhen
          myRole="owner"
          additionalCondition={(currentUser) =>
            !currentUser.org_enforced_second_factor_auth &&
            !currentUser.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.TwoFactorVerification)
          }
        >
          <StyledDiv isFlowRevamped={user.isAccountAndSettingsRevampEnabled}>
            <Merchant2FASettings />
          </StyledDiv>
        </ShowWhen>

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
  user: state.session.user,
});

export default connect(mapStateToProps, { sendInvitation, openModal, closeModal })(
  ManageTeamContainer,
);
