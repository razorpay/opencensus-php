import { Component } from 'react';
import { connect } from 'react-redux';
import Spinner from 'rzp/ui/Spinner';
import { fetchActivationDetails } from 'merchant/modules/activation';
import ActivationWizard from './ActivationWizard';

@connect(state => state.activation, { fetchActivationDetails })
export default class ActivationContainer extends Component {
  componentWillMount() {
    this.props.fetchActivationDetails();
  }

  render() {
    let { loading, data } = this.props;

    return (
      <div style={{ background: '#fff' }}>
        {loading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <ActivationWizard data={data} accountId={this.props.accountId} />
        )}
      </div>
    );
  }
}
