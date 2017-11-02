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
    merchant: null,
  };

  componentWillMount() {
    adminFetch({
      route_name: 'org_fieldmap_get_by_entity',
      url_params: {
        entity: 'admin_lead',
      },
    }).then(response => {
      let newState = {
        merchant: {},
      };

      if (response) {
        newState.fields = response.fields;
        newState.fields.forEach(field => {
          //Add default values else null
          if (field === 'promo_code') {
            newState.merchant[field] = 'RP_StartUP';
          } else if (field === 'merchant_type') {
            newState.merchant[field] = 'stp';
          } else {
            newState.merchant[field] = null;
          }
        });
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
      console.log(response);
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
      <InviteForm fields={this.state.fields} onInvite={this.handleInvite} />
    );
  }
}

export function showEntity(collection) {
  openModal(<AddInvites collection={collection} model={this} />);
}
