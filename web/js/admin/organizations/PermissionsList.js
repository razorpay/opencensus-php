import React, { Component } from 'react';
import { observer } from 'mobx-react';
import axios from 'axios';

import SimpleTable from 'ui/SimpleTable';
import { CheckField } from 'ui/Field';

import { adminFetch } from 'util/fetch';

import Model from './model';

const permsFields = [
  ['', item => <CheckField defaultChecked={item.assignable} />],
  ['Permission', item => item.name],
  ['Category', item => item.merchant_detail],
  ['Workflow Enable', item => <CheckField defaultChecked={item.assignable} />],
  ['Assignable', item => (item.assignable ? 'Yes' : 'No')],
];

@observer
class PermissionsList extends Component {
  constructor() {
    super();
    this.model = new Model();
  }

  componentWillMount() {
    let { fetchAllPerms, fetchAssignablePerms, fetchOrg } = this.model;

    //fetch all permissions
    fetchAllPerms();

    //fetch permissions based on Add or Edit Org
    if (this.props.orgId) {
      fetchOrg(this.props.orgId);
    } else {
      fetchAssignablePerms();
    }
  }

  render() {
    const org = this.model.org;
    return (
      <div>
        <header>Permissions</header>
        <SimpleTable items={org.permissions} fields={permsFields} />
      </div>
    );
  }
}

export default PermissionsList;
