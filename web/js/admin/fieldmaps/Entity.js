import React, { Component } from 'react';

import { openSlider } from 'common/modal';
import { notifySuccess, closeSlider } from 'common/modal';

import { adminPost, adminPut, adminDelete } from 'util/fetch';

import FieldMapForm from './FieldMapForm';

export default class EditFieldMaps extends Component {
  save = data => {
    let adminFn = data.id ? adminPut : adminPost;
    let url_params = {
      orgId: this.props.model.org_id,
      ...(data.id ? { id: data.id } : null),
    };
    let route_name = data.id ? 'org_fieldmap_edit' : 'org_fieldmap_create';

    if (data.fields) data.fields = data.fields.split(',');

    if (data.id) delete data.id;

    return adminFn({
      body: data,
      route_name: route_name,
      url_params: url_params,
    }).then(_successNotify);
  };

  render() {
    return <FieldMapForm {...this.props.model} onSubmit={this.save} />;
  }
}

const _successNotify = response => {
  notifySuccess(
    'Field Map added successfully. Response: ' + JSON.stringify(response)
  );
  closeSlider();
};

export function removeEntity(collection) {
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
  openSlider(<EditFieldMaps collection={collection} model={this} />);
}
