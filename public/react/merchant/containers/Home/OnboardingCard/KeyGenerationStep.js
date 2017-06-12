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
    let params = {
      id: user.id,
    };
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
    let isLoading = this.props.isLoading || this.state.isLoading;

    let header = `Integrate Razorpay in ${modeFormatted} Mode`;
    let headerDesc = (
      <div>
        <Link to="/keys">Generate Keys</Link> and
        {' '}
        <a
          href="https://docs.razorpay.com/docs/getting-started"
          target="_blank"
        >
          start integration
        </a>
        {' '}
        <i class="icon icon-external-link" />
      </div>
    );

    if (user.isActivated) {
      if (!this.state.keysGenerated) {
        if (mode === 'test') {
          header = 'Integrate Razorpay in Live Mode';
          headerDesc = (
            <div>
              <a>Switch to Live Mode</a> & generate live keys
            </div>
          );
        }
      }
    }

    if (this.state.keysGenerated) {
      headerDesc = (
        <div>
          Key Generated. Show
          {' '}
          <a
            href="https://docs.razorpay.com/docs/getting-started"
            target="_blank"
          >
            Integration docs
          </a>
          {' '}
          <i class="icon icon-external-link" />
        </div>
      );
    }

    return (
      <div class="Onboarding__Step">
        <div class="media">
          <div class="media-left">
            <a href="#">
              <img class="media-object" />
            </a>
          </div>
          {isLoading
            ? <div class="media-body">
                <div class="media-heading">
                  <PlaceholderLoader style={{ height: '16px' }} />
                </div>
                <PlaceholderLoader />
              </div>
            : <div class="media-body">
                <div class="media-heading">{header}</div>
                {headerDesc}
              </div>}
        </div>
      </div>
    );
  }
}
