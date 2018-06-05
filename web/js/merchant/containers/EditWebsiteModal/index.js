import React, { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';

import { merchantFetch } from 'rzp/utils/ajax';
import ModalHeader from 'rzp/ui/ModalHeader';

import { Modal, ModalContent } from 'component/Modal';

const EditModalContent = ({
  onSubmit,
  onCancel,
  websiteUrl,
  onWebsiteChange,
}) => (
  <form
    className={`edit-website-details-form${
      onCancel ? ' has-cancel-button' : ''
    }`}
    onSubmit={onSubmit}
  >
    <div className="form-group">
      <small>Your website/app should contain these pages: </small>
      About Us, Contact Us, Privacy Policy, Terms & Conditions, Refund Policy &
      Pricing.
    </div>
    <div className="form-group">
      <label>Website/App Link</label>
      <input
        className="form-control"
        type="url"
        value={websiteUrl}
        onKeyup={onWebsiteChange}
        placeholder="ex: http://www.xyz.com"
      />
    </div>
    <div className="form-group">
      <button type="button" className="btn btn-default" onClick={onCancel}>
        Cancel
      </button>
      <button className="btn btn-primary">Add Details</button>
    </div>
  </form>
);

@withRouter
@connect(state => ({
  business_website: state.session.user.business_website,
  has_key_access: state.session.user.has_key_access,
}))
class EditWebsiteModal extends Component {
  constructor(props) {
    super(props);

    this.state = {
      websiteUrl: props.business_website,
      loading: false,
    };

    // if someone directly visits the URL from browser history,
    // we should not render anything
    this.shouldRender = !this.props.has_key_access;

    if (!this.shouldRender) {
      return this.props.history.push('/');
    }

    this.onClose = this.onClose.bind(this);
    this.onSubmit = this.onSubmit.bind(this);
    this.onWebsiteChange = this.onWebsiteChange.bind(this);
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.business_website !== this.props.business_website) {
      this.setState({
        websiteUrl: nextProps.business_website,
      });
    }
  }

  onSubmit(e) {
    e.preventDefault();

    const { websiteUrl } = this.state;

    merchantFetch({
      url: 'merchant/activation/update_website_details',
      mode: 'live',
      method: 'post',
      data: { business_website: websiteUrl },
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

    return (
      <Modal onClose={this.onClose}>
        <ModalContent header="Add Website/App Details">
          <EditModalContent
            onSubmit={this.onSubmit}
            onCancel={this.onClose}
            websiteUrl={this.state.websiteUrl}
            onWebsiteChange={this.onWebsiteChange}
          />
        </ModalContent>
      </Modal>
    );
  }
}

export default EditWebsiteModal;
