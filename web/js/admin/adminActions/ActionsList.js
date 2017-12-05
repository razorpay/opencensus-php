import React, { Component } from 'react';
import { openModal, confirm } from 'common/modal';
import * as actionModals from './actionModals';
import Table from 'ui/Table';
import BaseModal from 'ui/BaseModal';
import user from 'admin/user';

// Access actions using actions.FileName (FileName is the export name of that modal content in entity/index.js)
const rows = [];

Object.keys(actionModals).map(key => {
  var permission = actionModals[key].permission;
  var permissions = user.permissions;

  if (!permission || permissions.find(perm => permission === perm)) {
    rows.push(actionModals[key]);
  }
});

export default class AdminActionsList extends Component {
  render() {
    return (
      <div className="limited box">
        <header>Actions</header>
        <Table header={false} fields={fields} items={rows} onClick={onClick} />
      </div>
    );
  }
}

const fields = [['', item => item && item.title]];

function onClick(e) {
  openModal(
    <BaseModal header={this.title}>
      <this />
    </BaseModal>
  );
}
