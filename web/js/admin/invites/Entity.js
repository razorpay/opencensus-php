import React, { Component } from 'react';
import Form from 'ui/Form';
import Field from 'ui/Field';
import {
  openModal,
  closeModal,
  notifySuccess,
  notifyError,
} from 'common/modal';
import { adminFetch, adminPost } from 'util/fetch';

import InviteForm from './InviteForm';
import DetailsModal from './DetailsModal';

class AddInvites extends Component {
  state = {
    pending: true,
    fields: null,
  };

  componentWillMount() {
    adminFetch({
      route_name: 'org_fieldmap_get_by_entity',
      url_params: {
        entity: 'admin_lead',
      },
    }).then(response => {
      let newState = {};

      if (response) {
        newState.fields = response.fields;
      }

      newState.pending = false;

      this.setState(newState);
    });
  }

  handleInvite = body => {
    return adminPost({
      route_name: 'admin_lead_create',
      body,
    }).then(response => {
      if (response) {
        this.props.collection.items.push(response);
        notifySuccess(
          `Success! Invitation has been sent to ${body.contact_email}`
        );
      } else {
        response.errors.forEach(err => notifyError(err));
      }
      closeModal();
    });
  };

  render() {
    if (this.state.pending) {
      return <div class="spinner" />;
    }

    return (
      //Default values as props
      <InviteForm
        fields={this.state.fields}
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
