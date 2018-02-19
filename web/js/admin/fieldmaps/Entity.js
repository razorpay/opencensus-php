import React, { Component } from 'react';

import { openModal, closeModal, notifySuccess } from 'common/modal';

import { adminPost, adminPut, adminDelete } from 'common/fetch';

import FieldMapForm from './FieldMapForm';
import { prevent } from 'common/util';

export default class EditFieldMaps extends Component {
  save = data => {
    let id = data.id || null;
    let { org_id } = this.props.model;
    let requestFn = id ? adminPut : adminPost;

    if (data.fields) {
      data.fields = data.fields.replace(/\s/g, '').split(',');
      data.fields = data.fields.filter(field => field !== '');
    }

    if (id) delete data.id;

    return requestFn({
      url: id
        ? `live/orgs/${org_id}/field-map/{id}`
        : `live/orgs/${org_id}/field-map`,
      data,
    }).then(response => {
      if (response) {
        notifySuccess(
          'Field Map added successfully. Response: ' + JSON.stringify(response)
        );
        if (!id) {
          this.props.model.push(response);
        }
        closeModal();
      }
    });
  };

  render() {
    return <FieldMapForm {...this.props.model} onSubmit={this.save} />;
  }
}

export function removeEntity(e) {
  prevent(e);
  adminDelete(`orgs/${this.org_id}/field-map/${this.id}`).then(response => {
    notifySuccess('Field Map deleted.' + JSON.stringify(response));
    this.collection.items.remove(this);
  });
}

export function showEntity(collection, item) {
  openModal(<EditFieldMaps collection={collection} model={this} />);
}
