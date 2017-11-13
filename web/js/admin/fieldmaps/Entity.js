import React, { Component } from 'react';

import { openModal, closeModal, notifySuccess } from 'common/modal';

import { adminPost, adminPut, adminDelete } from 'util/fetch';

import FieldMapForm from './FieldMapForm';
import { prevent } from 'util/index';

export default class EditFieldMaps extends Component {
  save = data => {
    let id = data.id || null;
    let { org_id } = this.props.model;
    let adminFn = id ? adminPut : adminPost;

    let url_params = {
      orgId: org_id,
      ...(id ? { id: id } : null),
    };

    let route_name = id ? 'org_fieldmap_edit' : 'org_fieldmap_create';

    if (data.fields) {
      data.fields = data.fields.replace(/\s/g, '').split(',');
      data.fields = data.fields.filter(field => field !== '');
    }

    if (id) delete data.id;

    return adminFn({
      body: data,
      route_name: route_name,
      url_params: url_params,
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
  adminDelete({
    route_name: 'org_fieldmap_delete',
    url_params: {
      orgId: this.org_id,
      id: this.id,
    },
  }).then(response => {
    notifySuccess('Field Map deleted.' + JSON.stringify(response));
    this.collection.items.remove(this);
  });
}

export function showEntity(collection, item) {
  openModal(<EditFieldMaps collection={collection} model={this} />);
}
