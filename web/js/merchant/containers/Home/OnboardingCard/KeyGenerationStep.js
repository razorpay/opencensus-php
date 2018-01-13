import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import { fetchKeys } from 'merchant/modules/keys';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';
// import GenerateKeySVG from 'styles/assets/integrate-generatekey.svg';
// import IntegrateSVG from 'styles/assets/integrate.svg';

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

  switchToLiveMode = () => {
    LocalStorageService.setItem('rzp_mode', 'live');
    window.location.reload();
  };

  render() {
    let { user, mode, modeFormatted } = this.props;
    let isLoading = this.state.isLoading;

    let header = `Integrate Razorpay in ${modeFormatted} Mode`;
    let svgSrc = GenerateKeySVG;
    let headerDesc = (
      <div>
        <Link to="/keys">Generate Keys</Link> and{' '}
        <a
          href="https://docs.razorpay.com/docs/getting-started"
          target="_blank"
          onClick={e => e.stopPropagation()}
        >
          start integration
        </a>{' '}
        <i class="icon icon-external-link" />
      </div>
    );

    if (user.isActivated) {
      if (!this.state.keysGenerated) {
        if (mode === 'test') {
          header = 'Integrate Razorpay in Live Mode';
          headerDesc = (
            <div>
              <a
                onClick={e => {
                  e.stopPropagation();
                  this.switchToLiveMode();
                }}
              >
                Switch to Live Mode
              </a>{' '}
              & generate live keys
            </div>
          );
        }
      }
    }

    if (this.state.keysGenerated) {
      svgSrc = IntegrateSVG;
      headerDesc = (
        <div>
          Key Generated. Show{' '}
          <a
            href="https://docs.razorpay.com/docs/getting-started"
            target="_blank"
            onClick={e => e.stopPropagation()}
          >
            Integration docs
          </a>{' '}
          <i class="icon icon-external-link" />
        </div>
      );
    }

    return (
      <Link class="Onboarding__Step" to="/keys">
        <div class="media">
          <div class="media-left">
            <img class="media-object" src={svgSrc} />
          </div>
          {isLoading ? (
            <div class="media-body">
              <div class="media-heading">
                <PlaceholderLoader style={{ height: '16px', width: '90%' }} />
              </div>
              <PlaceholderLoader style={{ width: '90%' }} />
            </div>
          ) : (
            <div class="media-body">
              <div class="media-heading">{header}</div>
              {headerDesc}
            </div>
          )}
        </div>
      </Link>
    );
  }
}
