import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';

import { RZPFeatures } from 'rzp/utils/constants';

import ShowWhen from 'merchant/components/ShowWhen';

import PaymentLinksList from 'merchant/containers/PaymentLinks/Links/List';
import BatchUploadList from 'merchant/containers/PaymentLinks/BatchUpload/List';

import TestModeBanner from 'merchant/containers/TestModeBanner';
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
      invoices: state.invoices,
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
    if (nextProps.invoices.loading !== this.props.invoices.loading) {
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
      invoices: props.invoices,
    };

    const isPaymentLinksEnabled = getIsPaymentLinksEnabled(data);

    let showOnboarding = !isPaymentLinksEnabled;

    if (isPaymentLinksEnabled) {
      showOnboarding = getIsAllowedResetPaymentLinksOnBoarding(data);
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
    );
  }
}
