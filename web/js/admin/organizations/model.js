import { observable, action } from 'mobx';

import { adminFetch } from 'util/fetch';

class Model {
  @observable
  org = {
    permissions: [],
    workflowPermissions: {},
    selectedPermissions: {},
  };

  @action
  fetchOrg = orgId => {
    let params = {
      route_name: 'org_get',
      url_params: {
        id: orgId,
      },
    };
    adminFetch(params).then(response => {
      let perms = {};
      perms = response.permissions.map(perm => ({ [perm.id]: true }));
      this.org.selectedPermissions = {
        ...this.org.selectedPermissions,
        ...perms,
      };
    });
  };

  @action
  fetchAllPerms = () => {
    let params = {
      route_name: 'permission_get_by_type',
      url_params: { type: 'all' },
    };
    adminFetch(params).then(response => {
      this.org.permissions = response.items;
    });
  };

  @action
  fetchAssignablePerms = () => {
    let params = {
      route_name: 'permission_get_by_type',
      url_params: { type: 'assignable' },
    };
    adminFetch(params).then(response => {
      let perms = {};
      perms = response.items.map(perm => ({ [perm.id]: true }));
      this.org.selectedPermissions = {
        ...this.org.selectedPermissions,
        ...perms,
      };
    });
  };
}
export default Model;
