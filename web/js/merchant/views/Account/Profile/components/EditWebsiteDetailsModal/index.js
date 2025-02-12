import React, { Component } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';

import ModalHeader from 'common/ui/ModalHeader';
import { autoPrefixUrls } from 'common/utils/rzp-utils';
import User from 'merchant/models/User';
import { updateSession } from 'merchant/reducers/session';
import { merchantFetch } from 'merchant/utils/ajax';
import { showNotification } from 'merchant_common/reducers/notifications';

import EditWebsite from './components/EditWebsite';
import SuccessModalContent from './components/SuccessModalContent';

class EditWebsiteDetails extends Component {
  onSubmit = (form) => {
    // eslint-disable-next-line no-shadow
    const { user, showNotification } = this.props;

    return merchantFetch({
      url: 'merchant/activation/update_website_details',
      method: 'put',
      mode: 'live',
      data: { business_website: autoPrefixUrls(form.business_website) },
    })
      .then((response) => {
        if (response.success) {
          //update user session details
          const newUser = new User({
            ...user,
            business_website: response.data.business_website,
            has_key_access: response.data.has_key_access,
          });

          this.props.updateSession({
            user: newUser,
            mode: this.props.mode,
          });

          // eslint-disable-next-line babel/no-unused-expressions
          this.props.onWebsiteAdd && this.props.onWebsiteAdd();
          showNotification({
            type: 'success',
            message: 'Thank you for providing website.',
          });
          this.props.onClose();
        }
      })
      .catch((err) => {
        if (err.errors && err.errors[0]) {
          showNotification({
            type: 'error',
            message: err.errors[0],
          });
        } else {
          showNotification({
            type: 'error',
            message: 'Failed to add website!',
          });
        }
      });
  };

  render() {
    const { business_website } = this.props.user;

    return (
      <div className="edit-website-modal">
        <ModalHeader title="Add Website/App Details" onCloseClick={this.props.onClose} />
        <div className="modal-body">
          {business_website ? (
            <SuccessModalContent onClose={this.props.onClose} />
          ) : (
            <EditWebsite onSubmit={this.onSubmit} onCancel={this.props.onClose} />
          )}
        </div>
      </div>
    );
  }
}

export default compose(
  connect(
    (state) => ({
      user: state.session.user,
      mode: state.session.mode,
    }),
    {
      updateSession,
      showNotification,
    },
  ),
)(EditWebsiteDetails);
