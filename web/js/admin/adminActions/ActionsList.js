import React, { Component } from 'react';
import { openModal, confirm } from 'common/modal';
import * as actionModals from './actionModals';
import SimpleTable from 'ui/SimpleTable';

// Access actions using actions.FileName (FileName is the export name of that modal content in entity/index.js)
const rows = Object.keys(actionModals).map(key => actionModals[key]);

export default class AdminActionsList extends Component {
  render() {
    return (
      <div className="box">
        <header>Actions</header>
        <SimpleTable fields={fields} items={rows} onClick={onClick} />
      </div>
    );
  }
}

const fields = [['', item => item && item.title]];

function onClick(e) {
  openModal(<this />);
}
