import React, { Component } from 'react';
import Form from 'ui/Form';
import Table from 'ui/Table';
import Field from 'ui/Field';
import Collection from 'model/collection';
import { adminFetch } from 'util/fetch';
import { observer } from 'mobx-react';
import { showEntity } from './Entity';
import { bool } from 'ui/Item';
import { notifyError } from 'common/modal';

@observer
export default class RoleList extends Component {
  collection = new Collection({
    fetchFn: adminFetch,
    data: {
      route_name: 'role_get_multiple',
    },
  });

  state = {
    pending: true,
  };

  componentWillMount() {
    adminFetch({
      route_name: 'permission_get_multiple',
    }).then(data => {
      this.setState({
        pending: false,
      });
    });
  }

  onSubmit = filters => this.collection.setFilters(filters);
  showEntity = showEntity.bind(null, this.collection);

  render() {
    if (this.state.pending) {
      return 'loading...';
    }
    return (
      <div class="list-container">
        <div class="box">
          <header>
            Roles
            <div class="btn" onClick={this.showEntity}>
              Add Role
            </div>
          </header>
          <Form onSubmit={this.onSubmit} class="filters">
            <Field name="q" label="Search" />
          </Form>
        </div>
        <Table model={this.collection} fields={fields} onClick={showEntity} />
      </div>
    );
  }
}

const fields = [
  ['Id', item => item.id],
  ['Name', item => item.name],
  ['Description', item => item.description],
  ['Action', item => <div class="link danger">Delete</div>],
];
