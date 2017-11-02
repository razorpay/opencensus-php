import React, { Component } from 'react';
import Form from 'ui/Form';
import Field from 'ui/Field';
import { openModal } from 'common/modal';
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
          newState.merchant[field] = null;
        });
      }

      newState.pending = false;

      this.setState(newState);
    });
  }

  handleSave = body => {
    console.log(body);
  };

  render() {
    if (this.state.pending) {
      return <div class="spinner" />;
    }

    return (
      <InviteForm fields={this.state.fields} handleSave={this.handleSave} />
    );
  }
}

export function showEntity(collection) {
  openModal(<AddInvites collection={collection} model={this} />);
}
