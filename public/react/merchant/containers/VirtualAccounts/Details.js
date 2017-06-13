import { Component } from 'react';
import { connect } from 'react-redux';
import VirtualAccountDetails from 'merchant/components/VirtualAccounts/Details';
import * as VirtualAccountActions
  from 'merchant/modules/virtualaccounts/details';

@connect(state => state.virtualaccount, VirtualAccountActions)
export default class VirtualAccountDetailsContainer extends Component {
  componentWillMount() {
    debugger;
    this.props.fetchItem(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id);
    }
  }

  render() {
    let { loading, error, entity } = this.props;
    debugger;
    let statusMsg = {};

    if (error) {
      statusMsg = {
        type: 'error',
        message: this.props.error,
      };
    }

    return (
      <VirtualAccountDetails
        virtualaccount={entity}
        isLoading={loading}
        statusMsg={statusMsg}
      />
    );
  }
}
