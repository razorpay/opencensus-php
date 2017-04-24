import { Component } from 'react';
import { connect } from 'react-redux';
import Header from 'rzp/ui/Header';
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
      <div class="react-root">
        <Header title="Activation Form" showMode={false} />

        <div class="content-wrapper">
          {loading
            ? <div class="page-spinner-container">
                <Spinner />
              </div>
            : <ActivationWizard data={data} />}
        </div>
      </div>
    );
  }
}
