import React, { Component } from 'react';
import Form from 'ui/Form';
import Field, { SelectField, CheckField } from 'ui/Field';
import { openSlider } from 'common/modal';
import SimpleTable from 'ui/SimpleTable';
import { adminFetch, adminPut } from 'util/fetch';

class EditOrg extends Component {
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
    console.log(body);
    let params = {
      content_type: 'application/json',
      route_name: 'org_edit',
    };
    if (this.props.model) {
      params.url_params = {
        id: this.props.model.id,
      };
    }

    return adminPut({ body, params });
  };

  init(props) {
    if (props.model) {
      Promise.all([
        adminFetch({
          route_name: 'permission_get_by_type',
          url_params: {
            type: 'all',
          },
        }),

        adminFetch({
          route_name: 'org_get',
          url_params: {
            id: props.model.id,
          },
        }),
      ]);
    }
  }

  render() {
    let { id, business_name, display_name, email, email_domains } =
      this.props.model || {};

    return (
      <div>
        <header>Edit Org - {id} </header>
        <Form onSubmit={this.save}>
          <Field label="Email" required defaultValue={email} />
          <Field label="Business Name" required defaultValue={business_name} />
          <Field label="Display Name" required defaultValue={display_name} />
          {/* <Field name="name" label="Full Name" required defaultValue={name} />
          <CheckField
            name="allow_all_merchants"
            label="Allow All Merchants"
            defaultValue={allow_all_merchants}
          />
          <CheckField
            name="disabled"
            label="Disabled"
            defaultValue={disabled}
          /> */}
          <br />
          <button>Save</button>
        </Form>
      </div>
    );
  }
}

export function showEntity(collection) {
  openSlider(<EditOrg collection={collection} model={this} />);
}

const fields = [
  ['Organization ID', item => item.id],
  ['Business Name', item => item.business_name],
  ['Display Name', item => item.display_name],
  ['Email', item => item.email],
  ['Email Domain', item => item.email_domains.join(',')],
  ['Actions', item => <Actions id={item.id} />],
];
