import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { fetchKeys } from 'merchant/modules/keys';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';

@connect(null, { fetchKeys })
export default class KeyGenerationStep extends Component {
  state = {
    isLoading: true,
  };

  componentWillMount() {
    let user = this.props.user;
    let params = {};
    params.mode = user.isActivated ? 'live' : 'test';

    this.props.fetchKeys(params).then(({ data }) => {
      this.setState({
        keysGenerated: data.items.length,
        isLoading: false,
      });
    });
  }

  render() {
    let { user, mode, modeFormatted } = this.props;
    let isLoading = this.state.isLoading;

    return (
      <a className={`Onboarding__Step ${isLoading ? ' loading' : ''}`}>
        <div className="media">
          <div className="media-body">
            <b>
              <span>Integrate in Test Mode</span>
              {isLoading && <PlaceholderLoader />}
            </b>
            <div className="step-desc">
              <span>Go through Integration Docs</span>
              {isLoading && <PlaceholderLoader />}
            </div>
          </div>
          <div className="media-arrow">
            <i className="i i-chevron-right" />
          </div>
        </div>
      </a>
    );
  }
}
