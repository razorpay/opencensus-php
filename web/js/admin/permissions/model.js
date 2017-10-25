import { observable, action } from 'mobx';
import axios from 'axios';

import { adminFetch } from 'util/fetch';

export default class Permissions {
  @observable permission = {};
  @observable roles = [];
  @observable orgs = [];
  @observable selected_orgs = {};
  @observable workflow_orgs = {};

  @action
  fetchAll = id => {
    let self = this;
    if (id) {
      axios
        .all([
          _fetchPermFn('permission_get', id),
          _fetchPermFn('permission_get_roles', id),
          _fetchPermFn('org_get_multiple'),
        ])
        .then(
          axios.spread(function(permission, roles, orgs) {
            //Set orgs
            permission.orgs.forEach(org => {
              self.selected_orgs[org.id] = true;
            });
            permission.workflow_orgs.forEach(org => {
              self.workflow_orgs[org.id] = true;
            });

            //Set

            self.permission = { ...permission };
            self.roles = roles.items;
            self.orgs = orgs.items;
          })
        );
    }
  };

  @action
  selectAllOrg = isChecked => {
    this.selected_orgs = {};
    if (!isChecked) {
      this.workflow_orgs = {};
    } else {
      this.orgs.forEach(org => {
        this.selected_orgs[org.id] = true;
      });
    }
  };

  @action
  selectOrg = (isChecked, id) => {
    console.log(isChecked);
    this.selected_orgs[id] = !this.selected_orgs[id];

    if (this.workflow_orgs[id]) {
      this.workflow_orgs[id] = false;
    }
  };
}

function _fetchPermFn(route, id) {
  return adminFetch({
    route_name: route,
    ...(id ? { url_params: { id: id } } : null),
  });
}
