import React, { Component } from 'react';
import { openModal, confirm } from 'common/modal';
import * as actionModals from './actionModals';
import Table from 'ui/Table';
import { ModalContent } from 'component/Modal';
import user from 'admin/user';
import NavBar from 'admin/scrooge/NavBar';

// Access actions using actions.FileName (FileName is the export name of that modal content in entity/index.js)
const rows = [];

Object.keys(actionModals).map(key => {
  let permission = actionModals[key].permission;
  let permissions = user.permissions;

  if (!permission || permissions.find(perm => permission === perm)) {
    let actionModal = actionModals[key];
    if (typeof actionModal == 'function') {
      rows.push(actionModal);
    }
  }
});

export default class Actions extends Component {
  render() {
    return (
      <div className="list-container refunds-actions">
        <NavBar active="actions" />
        <div className="box">
          <header>Scrooge Actions</header>
          <Table
            header={false}
            fields={fields}
            items={rows}
            onClick={onClick}
          />
        </div>
      </div>
    );
  }
}

const fields = [['', item => item && item.title]];

function onClick(e) {
  openModal(
    <ModalContent header={this.title}>
      <this />
    </ModalContent>
  );
}
