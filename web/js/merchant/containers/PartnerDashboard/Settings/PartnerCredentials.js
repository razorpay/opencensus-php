import { Component } from 'react';
import { connect } from 'react-redux';

import { openModal, closeModal } from 'rzp/modules/modals';

import DetailRow from 'merchant/components/DetailRow';
import ViewCredentials from './ViewCredentials';

@connect(
  state => ({
    clientCredentials: state.applications.partnerApplication.clientCredentials,
  }),
  {
    openModal,
    closeModal,
  }
)
export default class PartnerCredentials extends Component {
  handleViewClick = mode => () => {
    const type = mode === 'test' ? 'dev' : 'prod';
    const { clientCredentials } = this.props;
    this.props.openModal({
      size: 'small',
      component: (
        <ViewCredentials mode={mode} credentials={clientCredentials[type]} />
      ),
    });
  };

  render() {
    return (
      <div class="list-group details-row-container">
        {/* live credentials */}
        <DetailRow
          label="Live Credentials"
          value={() => (
            <button onClick={this.handleViewClick('live')} class="btn-link">
              View
            </button>
          )}
        />

        {/* test credentials */}
        <DetailRow
          label="Test Credentials"
          value={() => (
            <button onClick={this.handleViewClick('test')} class="btn-link">
              View
            </button>
          )}
        />
      </div>
    );
  }
}
