import React, { Component } from 'react';
import Form from 'ui/Form';
import Field, { SelectField, CheckField, FileField } from 'ui/Field';
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
    let params = {
      content_type: 'application/json',
      route_name: 'org_edit',
      body: body,
    };

    if (this.props.model) {
      params.url_params = {
        id: this.props.model.id,
      };
    }

    return adminPut(params);
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
    let {
      id,
      business_name,
      display_name,
      email,
      email_domains,
      custom_code,
      from_email,
      signature_email,
      invoice_logo_url,
      login_logo_url,
      main_logo_url,
      allow_sign_up,
    } =
      this.props.model || {};

    return (
      <div>
        <header>Edit Org - {id} </header>
        <Form onSubmit={this.save}>
          <Field type="hidden" name="id" defaultValue={id} />
          <Field label="Email" name="email" required defaultValue={email} />
          <Field
            label="Business Name"
            name="business_name"
            required
            defaultValue={business_name}
          />
          <Field
            label="Display Name"
            name="display_name"
            required
            defaultValue={display_name}
          />
          <br />
          <Field
            label="Email Domains"
            name="email_domains"
            required
            defaultValue={email_domains}
          />
          <Field label="Hostname" />
          <SelectField label="Auth Type" name="auth_type">
            <option value="">Please select an auth type</option>
            <option value="password">Password</option>
            <option value="google_auth">Google Auth</option>
          </SelectField>
          <br />
          <Field
            label="Custom Code"
            name="custom_code"
            defaultValue={custom_code}
          />
          <Field
            label="From Email"
            name="from_email"
            defaultValue={from_email}
          />
          <Field
            label="Signature Email"
            name="signature_email"
            defaultValue={signature_email}
          />
          <br />
          <FileField label="Login Logo" name="login_logo_url" />
          <FileField label="Invoice Logo" name="invoice_logo_url" />
          <FileField label="Main Logo" name="main_logo_url" />
          <br />
          <CheckField
            label="Allow Sign Up"
            defaultValue={allow_sign_up}
            name="allow_sign_up"
          />
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
