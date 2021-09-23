import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import * as KeyActions from 'merchant/reducers/keys';
import { fetchAmount } from 'merchant/reducers/fetchTransaction';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import NewKey from 'merchant/views/Settings/Keys/components/NewKey';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';
import { getRecommendedProduct } from './productMap';
import ProductShimmer from './shimmer';

const RecommendationWidget = ({
  user,
  history,
  keys,
  generateKey,
  showNotification,
  closeModal,
  openModal,
  session,
  fetchKeys,
  fetchTransactionAmount,
  payment,
}) => {
  const [isApiKeyGenerated, setIsApiKeyGenerated] = useState(false);
  const [showGenerateKeyLoader, setShowGenerateKeyLoader] = useState(false);
  const landingProduct =
    localStorage.getItem('merchant_landing_page') || localStorage.getItem('default_product_page');

  const recommendedProduct = getRecommendedProduct(landingProduct);

  useEffect(() => {
    fetchKeys({ mode: session.mode }, session.user.has_key_access);

    if (landingProduct && recommendedProduct && typeof payment === 'object') {
      fetchTransactionAmount(user.created_at);
    }

    if (landingProduct && recommendedProduct && payment === 0) {
      analyticsTrack({
        objectName: 'Product recommendation widget',
        actionName: 'displayed',
        screen: 'home page',
        properties: {
          display_tag: 'Try for Side nav CTAs',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  }, [landingProduct]);

  if (!landingProduct || !recommendedProduct) {
    return null;
  }

  if (payment > 0) {
    localStorage.removeItem('merchant_landing_page');
    localStorage.removeItem('default_product_page');
  }

  const exploreProducts = () => {
    history.push(recommendedProduct[0].redirectUrl);

    analyticsTrack({
      objectName: recommendedProduct[0].segmentEventName,
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        display_tag: 'Try for Side nav CTAs',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  const showNewKeyModal = (key) => {
    openModal({
      component: <NewKey apiKey={key} />,
    });
  };

  const showGenerateKeyModal = () => {
    const merchantId = user?.id;

    // return immediately if api call is in progress
    if (showGenerateKeyLoader) {
      return;
    }

    setShowGenerateKeyLoader(true);

    generateKey({ merchantId })
      .then((response) => {
        setShowGenerateKeyLoader(false);
        const key = response.new || response;

        showNotification({
          type: 'success',
          message: 'New Key Generated',
        });

        analyticsTrack({
          objectName: `generate ${session.mode} key`,
          actionName: 'result',
          screen: 'home page',
          properties: {
            location: 'API Keys',
            status: 'Success',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        closeModal();
        showNewKeyModal(key);
        setIsApiKeyGenerated(true);
      })
      .catch((err) => {
        setShowGenerateKeyLoader(false);
        const errorMsg =
          (err?.errors && err.errors[0]) ||
          'Something went wrong. Please try again after sometime.';
        showNotification({
          type: 'error',
          message: errorMsg,
        });

        analyticsTrack({
          objectName: `generate ${session.mode} key`,
          actionName: 'result',
          screen: 'home page',
          properties: {
            location: 'API Keys',
            status: 'Failure',
            failureReason: err.errors[0],
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      });
  };

  const openPaymentOtions = () => {
    history.push('/paymentlinks/new');
  };

  const { primaryCardCta, onCardCtaClicked } = (() => {
    if (!user.activated) {
      return {
        primaryCardCta: 'Explore Now',
        onCardCtaClicked: exploreProducts,
      };
    }

    if (landingProduct === 'payment_gateway' && user.has_key_access) {
      if (isApiKeyGenerated || keys?.count > 0) {
        return {
          primaryCardCta: 'View API Key',
          onCardCtaClicked: exploreProducts,
        };
      }
      return {
        primaryCardCta: 'Generate API Key',
        onCardCtaClicked: showGenerateKeyModal,
      };
    }

    if (landingProduct === 'payment_link') {
      return {
        primaryCardCta: '+ Accept Payments',
        onCardCtaClicked: openPaymentOtions,
      };
    }

    return {
      primaryCardCta: '+ Accept Payments',
      onCardCtaClicked: exploreProducts,
    };
  })();

  return (
    <div>
      {payment === 0 ? (
        <div className="recommendation-widget">
          <div className="recommend-product">
            <div className="title">Get started with Razorpay product suite</div>
            <div className="active-product">
              <img src={recommendedProduct[0].imageCdn} />
              <div className="active-product__container">
                <div className="name">
                  <span onClick={exploreProducts}>{recommendedProduct[0].name}</span>
                </div>
                <div className="desc">{recommendedProduct[0].description}</div>
                <div className="explore">
                  {showGenerateKeyLoader && (
                    <span className="api-loader">
                      <span className="spin-btn visible" />
                    </span>
                  )}
                  <span className="card-cta" onClick={onCardCtaClicked}>
                    {primaryCardCta}
                  </span>
                  {!user.activated && <i class="i i-arrow-forward text-primary" />}
                </div>
              </div>
            </div>
          </div>
          <div className="more-product">
            <div className="more-product__title">Explore more products</div>
            <div className="product-container">
              <div className="prd-one">
                <img src={recommendedProduct[1].imageCdn} />
                <div className="container">
                  <div className="name">
                    <span
                      onClick={() => {
                        history.push(recommendedProduct[1].redirectUrl);
                        analyticsTrack({
                          objectName: recommendedProduct[1].segmentEventName,
                          actionName: 'clicked',
                          screen: 'home page',
                          properties: {
                            display_tag: 'Try for Side nav CTAs',
                            ...getCommonAnalyticsProperties(window.rzp_user),
                          },
                        });
                      }}
                    >
                      {recommendedProduct[1].name}
                    </span>{' '}
                    &gt;
                  </div>
                  <span className="desc">{recommendedProduct[1].shortDescription}</span>
                </div>
              </div>
              <div className="prd-two">
                <img src={recommendedProduct[2].imageCdn} />
                <div className="container">
                  <div className="name">
                    <span
                      onClick={() => {
                        history.push(recommendedProduct[2].redirectUrl);
                        analyticsTrack({
                          objectName: recommendedProduct[2].segmentEventName,
                          actionName: 'clicked',
                          screen: 'home page',
                          properties: {
                            display_tag: 'Try for Side nav CTAs',
                            ...getCommonAnalyticsProperties(window.rzp_user),
                          },
                        });
                      }}
                    >
                      {recommendedProduct[2].name}
                    </span>{' '}
                    &gt;
                  </div>
                  <span className="desc">{recommendedProduct[2].shortDescription}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      ) : (
        <ProductShimmer isMtuMerchant={payment > 0} />
      )}
    </div>
  );
};

const mapStateToProps = (state) => ({
  keys: state.keys,
  session: state.session,
  payment: state.transactionAmount.amount,
});

export default withRouter(
  connect(mapStateToProps, {
    ...KeyActions,
    ...ModalActions,
    ...NotificationsActions,
    fetchTransactionAmount: fetchAmount,
  })(RecommendationWidget),
);
