import { connect } from 'react-redux';
import PropTypes from 'prop-types';

import HeaderAction from 'rzp/ui/HeaderAction';
import ModalHeader from 'rzp/ui/ModalHeader';
import ShowWhen from 'merchant/components/ShowWhen';
import DocsLink from 'merchant/components/DocsLink';

import { sendInvitation } from 'merchant/modules/invitation';
import { openModal, closeModal } from 'rzp/modules/modals';

import InvitationsList from './Invitations/List';
import MembersList from './Members/List';
import Toggle2FA from './Toggle2FA';
import NewInvitation from './NewInvitation';

@connect(
  state => ({
    user: state.session.user.user,
  }),
  { sendInvitation, openModal, closeModal }
)
export default class ManageTeamContainer extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  inviteNewMember = () => {
    const visibleFields = {
      email: true,
      role: true,
    };

    const defaults = {
      sender_name: this.props.user.name,
      role: 'manager',
    };

    this.props.openModal({
      size: 'small',
      component: (
        <>
          <ModalHeader
            title="Invite New Member"
            onCloseClick={this.props.closeModal}
          />
          <div class="modal-body">
            <NewInvitation
              visibleFields={visibleFields}
              defaults={defaults}
              onSuccess={this.props.closeModal}
              onFormSubmit={this.props.sendInvitation}
              successMsg={data =>
                'Invitation has been successfully sent to ' + data.email
              }
              ctaText="Send Invitation"
            />
          </div>
        </>
      ),
    });
  };

  render() {
    return (
      <div class="content-wrapper content-sm" id="settings-content">
        <Toggle2FA />
        <HeaderAction>
          <div class="btn-toolbar pull-right">
            <DocsLink url="https://razorpay.com/docs/team-support/" />
            <ShowWhen myRole="owner">
              <button class="btn btn-primary" onClick={this.inviteNewMember}>
                Invite New Member
              </button>
            </ShowWhen>
          </div>
        </HeaderAction>
        <div class="ManageTeam--list">
          <InvitationsList {...this.props} />

          <div class="m-t" />

          <MembersList {...this.props} />
        </div>
      </div>
    );
  }
}
