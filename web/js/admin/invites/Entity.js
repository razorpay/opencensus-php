import React, { Component } from 'react';
import Form from 'ui/Form';
import Field from 'ui/Field';
import { openModal, closeModal, notifySuccess } from 'common/modal';
import { adminFetch, adminPost } from 'common/fetch';

import InviteForm from './InviteForm';
import DetailsModal from './DetailsModal';

class AddInvites extends Component {
  state = {
    pending: true,
  };

  // TODO: TEST whether orgId to be sent or not. Check 'org_fieldmap_get_by_entity' in api-route-map
  componentWillMount() {
    adminFetch(`live/field-map/entity/admin_lead`).then(response => {
      if (response) {
        this.fields = response.fields;
      }

      this.setState({ pending: false });
    });
  }

  handleInvite = body => {
    return adminPost({
      url: 'live/admin-lead',
      data: body,
    }).then(response => {
      if (response) {
        this.props.collection.items.push(response);
        closeModal();
        notifySuccess(
          `Success! Invitation has been sent to ${body.contact_email}`
        );
      }
    });
  };

  render() {
    if (this.state.pending) {
      return <div class="spinner center" />;
    }

    return (
      //Default values as props
      <InviteForm
        fields={this.fields}
        onInvite={this.handleInvite}
        merchant_type="stp"
        promo_code="RP_StartUP"
      />
    );
  }
}

export function showEntity(collection) {
  openModal(<AddInvites collection={collection} model={this} />);
}

export function showDetails() {
  openModal(<DetailsModal model={this} />);
}
