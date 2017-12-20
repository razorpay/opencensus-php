import React, { Component } from 'react';
import { observer } from 'mobx-react';
import {
  openModal,
  notifyError,
  notifySuccess,
  closeModal,
  notifyDone,
} from 'common/modal';
import fetch, { adminFetch, adminPost, adminDelete } from 'util/fetch';
import { isWorkflow } from 'util/index';
import RolesForm from './RolesForm';

import { prevent } from 'util/index';

@observer
class EditRole extends Component {
  state = {
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

      //customer request
      fetch({
        url: '/admin/generic',
        method: 'put',
        params: {
          route_name: 'role_edit',
          url_params: {
            '{roleId}': model.id,
          },
        },
        data,
      }).then(data => {
        if (data) {
          if (!isWorkflow(data)) {
            notifySuccess('Role edited successfully.');
            closeModal();
          }
        }
      });
    } else {
      body.permissions = this.state.selectedPerms;
      return adminPost({
        route_name: 'role_create',
        body,
      }).then(data => {
        if (data) {
          if (!isWorkflow(data)) {
            this.props.collection.push(data);
            notifySuccess('Roles added successfully.');
            closeModal();
          }
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
    return (
      <RolesForm
        {...this.state}
        name={this.props.model ? this.props.model.name : ''}
        description={this.props.model ? this.props.model.description : ''}
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

export function removeEntity(e) {
  prevent(e);
  let params = {
    route_name: 'role_delete',
    url_params: {
      roleId: this.id,
    },
  };
  return adminDelete(params).then(response => {
    if (response) {
      this.collection.items.remove(this);
      notifyDone();
    }
  });
}
