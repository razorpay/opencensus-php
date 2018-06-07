import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';

import User from 'merchant/models/User';
import { updateSession } from 'merchant/modules/session';
import { merchantFetch } from 'rzp/utils/ajax';
import ModalHeader from 'rzp/ui/ModalHeader';
import { Modal, ModalContent } from 'component/Modal';

import EditModalContent, {
  SuccessModalContent,
} from 'merchant/components/EditWebsiteModal/ModalContent';

@withRouter
@connect(
  state => ({
    user: state.session.user,
  }),
  {
    updateSession,
  }
)
class EditWebsiteModal extends Component {
  constructor(props) {
    super(props);
    this.state = {
      websiteUrl: props.user.business_website,
      loading: false,
    };

    // if someone directly visits the URL from browser history,
    // we should not render anything
    this.shouldRender = !this.props.user.has_key_access;

    if (!this.shouldRender) {
      return this.props.history.push('/');
    }

    this.onClose = this.onClose.bind(this);
    this.onSubmit = this.onSubmit.bind(this);
    this.onWebsiteChange = this.onWebsiteChange.bind(this);
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.user.business_website !== this.props.user.business_website) {
      this.setState({
        websiteUrl: nextProps.user.business_website,
      });
    }
  }

  onSubmit(e) {
    e.preventDefault();
    const { user } = this.props;
    const { websiteUrl } = this.state;

    merchantFetch({
      url: 'merchant/activation/update_website_details',
      mode: 'live',
      method: 'put',
      data: { business_website: websiteUrl },
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
  }

  onClose() {
    this.props.history.goBack();
  }

  onWebsiteChange(e) {
    this.setState({
      websiteUrl: e.target.value,
    });
  }

  render() {
    if (!this.shouldRender) {
      return null;
    }

    const { business_website } = this.props.user;

    return (
      <Modal onClose={this.onClose} className="edit-website-modal">
        <ModalContent header="Add Website/App Details">
          {business_website ? (
            <SuccessModalContent onClose={this.onClose} />
          ) : (
            <EditModalContent
              onSubmit={this.onSubmit}
              onCancel={this.onClose}
              websiteUrl={this.state.websiteUrl}
              onWebsiteChange={this.onWebsiteChange}
            />
          )}
        </ModalContent>
      </Modal>
    );
  }
}

export default EditWebsiteModal;
