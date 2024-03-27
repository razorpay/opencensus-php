import React from 'react';
import { Box, Heading } from '@razorpay/blade/components';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import Styled from 'styled-components';

import { withI18Service } from 'common/i18';
// eslint-disable-next-line no-restricted-imports
import HeaderAction from 'common/ui/HeaderAction';
import ModalHeader from 'common/ui/ModalHeader';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';
import DocsLink from 'merchant/components/DocsLink';
import ShowWhen from 'merchant/components/ShowWhen';
import rolesList from 'merchant/helpers/permissions/roles-list';
import { sendInvitation } from 'merchant/reducers/invitation';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

import PendingInvitationsList from './PendingInvitations/List';
import TeamMembersList from './TeamMembers/List';
import Merchant2FASettings from './components/Merchant2FASettings';
import NewInvitation from './components/NewInvitation';

const StyledDiv = Styled.div`
  margin-top:${({ isFlowRevamped }) => (isFlowRevamped ? '40px' : '0px')};
`;

// Note: The prop isRenderedFromPartnerRoute is added to render this component
// inside Partner Dashboard for managing POS agents. Slack url:
// https://razorpay.slack.com/archives/C0156ULAEFQ/p1702882355179019?thread_ts=1701754464.982339&cid=C0156ULAEFQ

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
    const { openModal, closeModal, isRenderedFromPartnerRoute, sendInvitation } = this.props;

    const defaults = {
      sender_name: this.props.user?.user?.name,
      role: isRenderedFromPartnerRoute ? rolesList.PARTNER_AGENT : rolesList.MANAGER,
    };
    openModal({
      size: 'small',
      component: (
        <>
          <ModalHeader title="Invite New Member" onCloseClick={closeModal} />
          <div className="modal-body">
            <NewInvitation
              isRenderedFromPartnerRoute={isRenderedFromPartnerRoute}
              visibleFields={visibleFields}
              defaults={defaults}
              onSuccess={closeModal}
              onFormSubmit={sendInvitation}
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
    const {
      user,
      i18: { isConfigTagEnabled },
      isRenderedFromPartnerRoute,
    } = this.props;

    return (
      <div className="content-wrapper content-sm" id="settings-content">
        {/* passing the new props to the HeaderAction component to support the m-web view */}
        <HeaderAction
          responsive
          target={
            user.isAccountAndSettingsRevampEnabled ? 'main > .content' : 'tabbed-container > header'
          }
        >
          <div className="btn-toolbar">
            {isRenderedFromPartnerRoute ? (
              <Box display="inline-block" marginLeft="5px" marginTop="5px">
                <Heading size="large">Add your POS Partner Agents Now!</Heading>
              </Box>
            ) : null}
            <div className="pull-right">
              <ShowWhen
                additionalCondition={() => !isConfigTagEnabled('documentation.documentation')}
              >
                <DocsLink
                  url="https://razorpay.com/docs/team-support/"
                  onClick={this.onDocumentationClick}
                />
              </ShowWhen>

              <ShowWhen additionalCondition={(userCurrent) => userCurrent.isAllowedEdit('team')}>
                {/* To make the CTAs on header to be sticky in the bottom need to add a wrapper to them added same */}
                <span className="cta-container">
                  <button className="btn btn-primary" onClick={this.inviteNewMember}>
                    Invite New Member
                  </button>
                </span>
              </ShowWhen>
            </div>
          </div>
        </HeaderAction>

        <ShowWhen
          myRole="owner"
          additionalCondition={(currentUser) =>
            !currentUser.org_enforced_second_factor_auth &&
            !isConfigTagEnabled('account.hide_2fa_verification')
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
  withI18Service(ManageTeamContainer),
);
