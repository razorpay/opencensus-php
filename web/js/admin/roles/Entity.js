import React, { Component } from 'react';
import Form from 'ui/Form';
import Field, { SelectField, CheckField } from 'ui/Field';
import { openSlider } from 'common/modal';
import Table from 'ui/Table';
import { adminFetch, adminPost } from 'util/fetch';

class EditUser extends Component {
  componentWillMount() {
    this.init(this.props);
  }

  componentWillReceiveProps(props) {
    this.init(props);
  }

  state = {
    pending: true,
  };

  save = body => {
    let params = {
      content_type: 'application/json',
      route_name: 'admin_edit',
    };
    if (this.props.model) {
      params.url_params = {
        adminId: this.props.model.id,
      };
    }

    return adminPost({ body, params });
  };

  init(props) {
    if (props.model) {
      Promise.all([
        adminFetch({
          route_name: 'org_fieldmap_get_by_entity',
          url_params: {
            entity: 'admin',
          },
        }),

        adminFetch({
          route_name: 'group_get_multiple',
        }),

        adminFetch({
          route_name: 'admin_get',
          url_params: {
            adminId: props.model.id,
          },
        }),
      ]);
    }
  }

  render() {
    let { name, allow_all_merchants, disabled } = this.props.model || {};

    return (
      <div>
        <header>Edit User</header>
        <Form onSubmit={this.save}>
          <Field name="name" label="Full Name" required defaultValue={name} />
          <CheckField
            name="allow_all_merchants"
            label="Allow All Merchants"
            defaultValue={allow_all_merchants}
          />
          <CheckField
            name="disabled"
            label="Disabled"
            defaultValue={disabled}
          />
          <br />
          <button>Save</button>
        </Form>
      </div>
    );
  }
}

export function showEntity(collection) {
  openSlider(<EditUser collection={collection} model={this} />);
}

const fields = [
  ['Group Id', item => item.id],
  ['Name', item => item.name],
  ['Description', item => item.description],
];
