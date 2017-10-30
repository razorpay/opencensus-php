import React, { Component } from 'react';
import Form from 'ui/Form';
import Field, { SelectField, CheckField } from 'ui/Field';
import { replaceSlider } from 'common/modal';
import Table from 'ui/Table';
import { adminFetch, adminPost } from 'util/fetch';

class EditRole extends Component {
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
      route_name: 'role_edit',
    };
    if (this.props.model) {
      params.url_params = {
        id: this.props.model.id,
      };
    }

    return adminPost({ body, params });
  };

  init(props) {
    if (props.model) {
      adminFetch({
        route_name: 'role_get',
        url_params: {
          id: props.model.id,
        },
      });
    }
  }

  render() {
    let { name, description, permissions } = this.props.model || {};

    return (
      <div>
        <header>Edit Role</header>
        <Form onSubmit={this.save}>
          <Field name="name" label="Name" required defaultValue={name} />
          <Field
            name="description"
            label="Description"
            required
            defaultValue={description}
          />
          <button>Save</button>
        </Form>
      </div>
    );
  }
}

export function showEntity(collection) {
  replaceSlider(<EditRole collection={collection} model={this} />);
}
