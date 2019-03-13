import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

import { fetchCommission } from 'merchant/modules/commission';

@withRouter
@connect(
  state => ({
    ...state.commission,
  }),
  { fetchCommission }
)
export default class CommissionEntityContainer extends Component {
  componentWillMount() {
    this.props.fetchCommission(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchCommission(nextProps.id);
    }
  }

  render() {
    return <div>Commission Entity View</div>;
  }
}
