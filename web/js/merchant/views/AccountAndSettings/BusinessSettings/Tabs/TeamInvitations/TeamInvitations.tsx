import React from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import { showNotification } from 'merchant_common/reducers/notifications';
import { acceptInvitation, rejectInvitation } from 'merchant/reducers/profile';
import { fetchUser } from 'merchant/reducers/session';
import Invitations from 'merchant/views/Account/Profile/components/Invitations';

const TeamInviations = ({
  user,
  showNotification,
  acceptInvitation,
  rejectInvitation,
  fetchUser,
}) => {
  const handleAcceptInvitation = (invite) => {
    return acceptInvitation(invite.id)
      .then(() => {
        showNotification({
          type: 'success',
          message: 'You have accepted the invite.',
        });
        setTimeout(() => {
          location.reload();
        }, 400);
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: err?.errors,
        });
      });
  };

  const handleRejectInvitation = (invite) => {
    return rejectInvitation(invite.id, user.user.id)
      .then(() => {
        showNotification({
          type: 'success',
          message: 'You have rejected the invite.',
        });
        fetchUser();
      })
      .catch((err) => {
        showNotification({
          type: 'error',
          message: err?.errors,
        });
      });
  };
  return (
    <Invitations
      invitations={user?.user?.invitations || []}
      onAcceptClick={handleAcceptInvitation}
      onRejectClick={handleRejectInvitation}
    />
  );
};
const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      showNotification,
      acceptInvitation,
      rejectInvitation,
      fetchUser,
    },
    dispatch,
  );
};

export default connect(mapStateToProps, mapDispatchToProps)(TeamInviations);
