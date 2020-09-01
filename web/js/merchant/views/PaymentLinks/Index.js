import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';

import { RZPFeatures } from 'merchant/helpers/data';

import ShowWhen from 'merchant/components/ShowWhen';

import PaymentLinksList from 'merchant/views/PaymentLinks/PaymentLinks/List';
import BatchUploadList from 'merchant/views/PaymentLinks/BatchUpload/List';

import PaymentButtonLaunchBanner from 'merchant/components/Announcements/PaymentButtonLaunch';
import TestModeBanner from 'merchant/components/TestModeBanner';
import { ShowWhenRoute } from 'merchant/components/ShowWhen';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';

import OnBoarding, {
  getIsPaymentLinksEnabled,
  getIsAllowedResetPaymentLinksOnBoarding,
} from './OnBoarding';

import QuickGuide, { getPaymentLinksQuickGuideIsClosed } from './QuickGuide';

@connect(
  state => {
    return {
      user: state.session.user,
      paymentlinks: state.paymentlinks,
      paymentLinksProductOnBoarding: getCurrentProductOnBoardingDetails(
        state,
        RZPFeatures.PL
      ),
    };
  },
  { handleProductQuickGuide }
)
export default class PaymentLinksContainer extends React.Component {
  componentWillReceiveProps(nextProps) {
    if (nextProps.paymentlinks.loading !== this.props.paymentlinks.loading) {
      this.initPaymentLinksOnboarding(nextProps);
    }
  }

  componentWillUnmount() {
    const { paymentLinksProductOnBoarding } = this.props;

    if (paymentLinksProductOnBoarding.isTour) {
      this.props.handleProductQuickGuide({
        ...paymentLinksProductOnBoarding,
        showOnboarding: false,
        isQuickGuideOpen: false,
        isTour: false,
      });
    }
  }

  initPaymentLinksOnboarding = (props = this.props) => {
    if (props.paymentLinksProductOnBoarding.isTour) {
      return;
    }

    const data = {
      user: props.user,
      merchantId: props.user.current,
      paymentlinks: props.paymentlinks,
    };

    const isPaymentLinksEnabled = getIsPaymentLinksEnabled(data);

    let showOnboarding = !isPaymentLinksEnabled;

    if (isPaymentLinksEnabled) {
      showOnboarding = getIsAllowedResetPaymentLinksOnBoarding(
        data.paymentlinks
      );
    }

    const paymentLinksProductOnBoarding = {
      ...props.paymentLinksProductOnBoarding,
      showOnboarding,
      isQuickGuideOpen: !getPaymentLinksQuickGuideIsClosed(props),
    };

    this.props.handleProductQuickGuide(paymentLinksProductOnBoarding);
  };

  render() {
    const {
      isQuickGuideOpen,
      showOnboarding,
    } = this.props.paymentLinksProductOnBoarding;

    if (showOnboarding) {
      return <OnBoarding />;
    }
    return (
      <React.Fragment>
        <PaymentButtonLaunchBanner productName="PaymentLinks" />

        <tabbed-container>
          {isQuickGuideOpen && <QuickGuide />}

          <header id="link-header">
            <NavLink exact to="/paymentlinks">
              Payment Links
            </NavLink>
            <ShowWhen
              additionalCondition={user =>
                user.isAllowedView('payment_links_batch_uploads') &&
                (!user.isSellerAppRole ||
                  user.isPaymentLinkBatchEnabledForSellerAppRole)
              }
            >
              <NavLink exact to="/paymentlinks/batchuploads">
                Batch Uploads
              </NavLink>
            </ShowWhen>
          </header>

          <TestModeBanner />

          <content>
            <Switch>
              <ShowWhenRoute
                path="/paymentlinks/batchuploads"
                component={BatchUploadList}
                additionalCondition={user =>
                  user.isAllowedView('payment_links_batch_uploads') &&
                  (!user.isSellerAppRole ||
                    user.isPaymentLinkBatchEnabledForSellerAppRole)
                }
              />
              <Route path="/paymentlinks" component={PaymentLinksList} />
            </Switch>
          </content>
        </tabbed-container>
      </React.Fragment>
    );
  }
}
