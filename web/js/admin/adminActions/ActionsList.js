import React, { Component } from 'react';
import { openModal, confirm } from 'common/modal';
import * as actionModals from './actionModals';
import Table from 'ui/Table';
import BaseModal from 'ui/BaseModal';

// Access actions using actions.FileName (FileName is the export name of that modal content in entity/index.js)
const rows = Object.keys(actionModals).map(key => actionModals[key]);

export default class AdminActionsList extends Component {
  render() {
    return (
      <BaseModal header="Actions">
        <Table fields={fields} items={rows} onClick={onClick} />
      </BaseModal>
    );
  }
}

const fields = [['', item => item && item.title]];

function onClick(e) {
  openModal(<this />);
}
