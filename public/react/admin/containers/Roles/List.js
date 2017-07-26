import { Component } from 'react';
import { connect } from 'react-redux';

export default class RolesListContainer extends Component {
  render() {
    return <DataTableWithStaticSearch rows={rows} columns={cols} />;
  }
}
