import React, { Component } from 'react';
import { connect } from 'react-redux';

import User from 'merchant/models/User';
import { updateSession } from 'merchant/modules/session';
import { merchantFetch } from 'rzp/utils/ajax';
import ModalHeader from 'rzp/ui/ModalHeader';

import EditWebsite, {
  SuccessModalContent,
} from 'merchant/components/EditWebsiteDetails/EditWebsite';

@connect(
  state => ({
    user: state.session.user,
  }),
  {
    updateSession,
  }
)
class EditWebsiteDetails extends Component {
  onSubmit = form => {
    const { user } = this.props;

    merchantFetch({
      url: 'merchant/activation/update_website_details',
      mode: 'live',
      method: 'put',
      data: { business_website: form.business_website },
    }).then(response => {
      if (response.success) {
        //update user session details
        const newUser = new User({
          ...user,
          business_website: response.data.business_website,
        });

        this.props.updateSession({
          user: newUser,
          mode: 'test',
        });
      }
    });
  };

  render() {
    const { business_website } = this.props.user;

    return (
      <div class="edit-website-modal">
        <ModalHeader
          title="Add Website/App Details"
          onCloseClick={this.props.onClose}
        />
        <div class="modal-body">
          {business_website ? (
            <SuccessModalContent onClose={this.props.onClose} />
          ) : (
            <EditWebsite
              onSubmit={this.onSubmit}
              onCancel={this.props.onClose}
            />
          )}
        </div>
      </div>
    );
  }
}

export default EditWebsiteDetails;
