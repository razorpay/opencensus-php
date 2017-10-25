import React, { Component } from 'react';
import axios from 'axios';
import { openSlider, openModal } from 'common/modal';
import { adminFetch } from 'util/fetch';
import PermForm from './PermissionForm';

class EditPerm extends Component {
  constructor() {
    super();
    this.state = {
      perms: null,
      roles: null,
      orgs: null,
    };
  }

  componentWillMount() {
    let self = this;
    if (this.props.model) {
      axios
        .all([
          _fetchPermFn('permission_get', this.props.model.id),
          _fetchPermFn('permission_get_roles', this.props.model.id),
          _fetchPermFn('org_get_multiple'),
        ])
        .then(
          axios.spread(function(perms, roles, orgs) {
            self.setState({
              perms,
              roles,
              orgs,
            });
          })
        );
    }
  }

  handleSelectAll = e => {
    console.log(e.target.value);
  };

  handleSelect = e => {
    console.log(e.target.value);
  };

  render() {
    return <PermForm {...this.props.model} {...this.state} />;
  }
}

export function showEntity(collection) {
  openSlider(
    <EditPerm
      collection={collection}
      model={this}
      onSelectAll={this.handleSelectAll}
      onSelect={this.handleSelect}
    />
  );
}

function _fetchPermFn(route, id) {
  return adminFetch({
    route_name: route,
    ...(id ? { url_params: { id: id } } : null),
  });
}
