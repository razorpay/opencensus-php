import React, { Component } from 'react';
import axios from 'axios';
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
    return axios({
      url: '/admin/generic',
      method: 'post',
      params: {
        route_name: 'admin_lead_create',
      },
      data: { body },
    }).then(response => {
      if (response.data.success) {
        notifySuccess(
          `Success! Invitation has been sent to ${body.contact_email}`
        );
      } else {
        closeModal();
        response.data.errors.forEach(err => notifyError(err));
      }
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
