import React, { Component } from 'react';
import { observer } from 'mobx-react';
import { openSlider, openModal } from 'common/modal';
import PermForm from './PermissionForm';
import Model from './model';

@observer
class EditPerm extends Component {
  constructor() {
    super();
    this.model = new Model();
  }

  componentWillMount() {
    let { id } = this.props.model;
    if (id) {
      this.model.fetchAll(id);
    }
  }

  handleSelectAll = e => {
    console.log(e.target.checked);
    this.model.selectAllOrg(e.target.checked);
  };

  handleSelect = (e, id) => {
    console.log(e.target.checked);
    this.model.selectOrg(e.target.checked, id);
  };

  render() {
    return (
      <PermForm
        {...this.props.model}
        {...this.model}
        onSelectAll={this.handleSelectAll}
        onSelect={this.handleSelect}
      />
    );
  }
}

export function showEntity(collection) {
  openSlider(<EditPerm collection={collection} model={this} />);
}

// function _fetchPermFn(route, id) {
//   return adminFetch({
//     route_name: route,
//     ...(id ? { url_params: { id: id } } : null),
//   });
// }
