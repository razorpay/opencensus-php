import { Component } from 'react';
import { connect } from 'react-redux';
import MediaCard from 'merchant/containers/Home/OnboardingCard/MediaCard';

import { setFeatures } from 'merchant/models/User';
import { fetchFeaturesAjax } from 'merchant/modules/config';

@connect(state => {
  return {
    user: state.session.user,
    mode: state.session.mode,
  };
})
export default class ProductActivationBanner extends Component {
  componentWillMount() {
    this.setState({
      isLoadingLiveModeFeatures: true,
      showProductActivationBanner: false,
    });

    // Check live mode features
    fetchFeaturesAjax(this.props.user.current, 'live')
      .then(data => {
        const liveFeatures = setFeatures(data.data.features);

        let isProductLiveActivated = false;
        isProductLiveActivated = liveFeatures.some(
          product => product.feature === this.props.feature && product.value
        );

        this.setState({
          isLoadingLiveModeFeatures: false,
          showProductActivationBanner: !isProductLiveActivated, // Show in test mode only if user not have this feature in live mode
        });
      })
      .catch(err => {
        this.setState({
          isLoadingLiveModeFeatures: false,
          showProductActivationBanner: true, // Showing as default fallback case
        });
      });
  }

  render() {
    const { productName, symbol, onActivate, productDocs } = this.props;

    if (!this.state.showProductActivationBanner) {
      return null;
    }

    return (
      <div className="feature-activation-banner">
        <MediaCard
          symbol={symbol}
          title={`Get Started with ${productName} in live mode`}
        >
          <div className="m-b">
            <div>
              You are currently in test mode. To integrate in test mode, you can
              go through the{' '}
              <a class="btn-link" target="_blank" href={productDocs}>
                documentation
              </a>.
            </div>
            <div>
              To activate {productName} in live mode, you can request for
              activation and we will get back to you in 1 working day.
            </div>
          </div>
          <button className="btn btn-default" onClick={onActivate}>
            Activate Now
          </button>
        </MediaCard>
      </div>
    );
  }
}
