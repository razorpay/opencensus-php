import React, { Component } from 'react';
import List from 'rzp/ui/Table/List';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';

export default class DetailsContainer extends Component {
  componentWillMount() {
    this.props.fetchItem(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id);
    }
  }

  render() {
    let { id, loading, error, item, rows } = this.props;
    let statusMsg = {};

    if (error) {
      statusMsg = {
        type: 'error',
        message: this.props.error,
      };
    }

    return (
      <div>
        <div class="panel-heading">
          {rows[0][0]}: <b>{id}</b>
        </div>
        {loading && <div class="page-spinner-container"><Spinner /></div>}
        {error && <Alert type="error" message={error} />}

        {item && <List rows={rows} item={item} />}
      </div>
    );
  }
}
