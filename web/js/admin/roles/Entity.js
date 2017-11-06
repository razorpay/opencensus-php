import React, { Component } from 'react';
import axios from 'axios';
import {
  openModal,
  notifyError,
  notifySuccess,
  closeModal,
} from 'common/modal';
import Table from 'ui/Table';
import { adminFetch, adminPost } from 'util/fetch';
import normalize from 'util/normalize';

import RolesForm from './RolesForm';

class EditRole extends Component {
  state = {
    name: '',
    description: '',
    allPerms: null,
    selectedPerms: null,
    pending: true,
  };

  componentWillMount() {
    let { model } = this.props;
    let selectedPerms = {},
      allPerms = [];

    adminFetch({
      route_name: 'permission_get_multiple',
    }).then(response => {
      if (response) {
        allPerms = response.items;
        if (model) {
          model.permissions.forEach(perm => {
            selectedPerms[perm.id] = true;
          });
        }

        this.setState({
          name: model ? model.name : '',
          description: model ? model.description : '',
          pending: false,
          allPerms,
          selectedPerms,
        });
      }
    });
  }

  save = body => {
    let { model } = this.props;
    let { selectedPerms } = this.state;

    if (model) {
      let data = { body };
      data.body.permissions = [];

      for (let sPerm in selectedPerms) {
        if (selectedPerms.hasOwnProperty(sPerm))
          data.body.permissions.push(sPerm);
      }
      //custome request
      axios({
        url: '/admin/generic',
        method: 'put',
        params: {
          route_name: 'role_edit',
          url_params: {
            '{roleId}': model.id,
          },
        },
        transformRequest: [
          (req, headers) => {
            let newData = normalize.serialize(data);
            headers['Content-Type'] =
              'application/x-www-form-urlencoded; charset=utf-8';
            return newData;
          },
        ],
      }).then(response => {
        if (response.data.success) {
          notifySuccess('Role edited successfully.');
          closeModal();
        } else {
          response.data.errors.forEach(err => notifyError(err));
        }
      });
    } else {
      body.permissions = this.state.selectedPerms;
      return adminPost({
        route_name: 'role_create',
        body,
      }).then(response => {
        if (response) {
          this.props.collection.items.push(response.data);
          notifySuccess('Roles added successfully.');
          closeModal();
        }
      });
    }
  };

  handleSelect = (e, id) => {
    let { selectedPerms } = this.state;
    if (e.target.checked) {
      selectedPerms[id] = true;
    } else {
      delete selectedPerms[id];
    }

    this.setState({ selectedPerms });
  };

  handleAllSelect = e => {
    let selectedPerms = {};

    if (e.target.checked) {
      this.state.allPerms.map(perm => {
        selectedPerms[perm.id] = true;
      });
    }

    this.setState({ selectedPerms });
  };

  render() {
    let state = { ...this.state };
    delete state.pending;

    if (this.state.pending) {
      return <div class="spinner" />;
    }

    return (
      <RolesForm
        {...state}
        onSubmit={this.save}
        onSelect={this.handleSelect}
        onSelectAll={this.handleAllSelect}
      />
    );
  }
}

export function showEntity(collection) {
  openModal(<EditRole collection={collection} model={this} />);
}
