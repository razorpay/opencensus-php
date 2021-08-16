import React, { useEffect, useState } from 'react';
import { withRouter } from 'react-router-dom';
import { merchantFetch } from 'merchant/utils/ajax';
import { getRecommendedProduct } from './productMap';
import { paiseToRupees, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from 'common/utils/analytics';

const RecommendationWidget = ({ user, history }) => {
  const [amount, setAmount] = useState(0);
  const landingProduct = localStorage.getItem('merchant_landing_page');
  const recommandedProduct = getRecommendedProduct(landingProduct);

  const getTransactionVoulme = async () => {
    const response = await merchantFetch({
      url: 'merchant/analytics',
      method: 'post',
      data: {
        filters: {
          default: [
            {
              created_at: { gte: user.activated_at, lte: new Date().getTime() },
              authorized_at: { gt: 0 },
            },
          ],
        },
        aggregations: {
          transactionVolume: {
            agg_type: 'sum',
            details: { index: 'payments', column: 'base_amount', mode: 'live' },
          },
        },
      },
    });

    const payment = response?.data?.transactionVolume
      ? paiseToRupees(response.data.transactionVolume?.result[0].value)
      : 0;
    setAmount(payment);
  };

  useEffect(() => {
    if (landingProduct && recommandedProduct && !!user.activated) {
      getTransactionVoulme();
    }

    if (landingProduct && recommandedProduct) {
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
  }, []);

  if (!landingProduct || !recommandedProduct) {
    return null;
  }

  if (amount > 0) {
    localStorage.removeItem('merchant_landing_page');
  }

  const exploreProducts = () => {
    history.push(recommandedProduct[0].redirectUrl);

    analyticsTrack({
      objectName: recommandedProduct[0].segmentEventName,
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        display_tag: 'Try for Side nav CTAs',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  return (
    <div className="recommendation-widget">
      <div className="recommend-product">
        <div className="title">Get started with Razorpay product suite</div>
        <div className="active-product">
          <img src={recommandedProduct[0].imageCdn} />
          <div className="active-product__container">
            <div className="name">
              <span onClick={exploreProducts}>{recommandedProduct[0].name}</span>
            </div>
            <div className="desc">{recommandedProduct[0].description}</div>
            <div className="explore">
              <span onClick={exploreProducts}>
                {user.activated ? '+ Accept Payments' : 'Explore Now'}
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
            <img src={recommandedProduct[1].imageCdn} />
            <div className="container">
              <div className="name">
                <span
                  onClick={() => {
                    history.push(recommandedProduct[1].redirectUrl);
                    analyticsTrack({
                      objectName: recommandedProduct[1].segmentEventName,
                      actionName: 'clicked',
                      screen: 'home page',
                      properties: {
                        display_tag: 'Try for Side nav CTAs',
                        ...getCommonAnalyticsProperties(window.rzp_user),
                      },
                    });
                  }}
                >
                  {recommandedProduct[1].name}
                </span>{' '}
                &gt;
              </div>
              <span className="desc">{recommandedProduct[1].description}</span>
            </div>
          </div>
          <div className="prd-two">
            <img src={recommandedProduct[2].imageCdn} />
            <div className="container">
              <div className="name">
                <span
                  onClick={() => {
                    history.push(recommandedProduct[2].redirectUrl);
                    analyticsTrack({
                      objectName: recommandedProduct[2].segmentEventName,
                      actionName: 'clicked',
                      screen: 'home page',
                      properties: {
                        display_tag: 'Try for Side nav CTAs',
                        ...getCommonAnalyticsProperties(window.rzp_user),
                      },
                    });
                  }}
                >
                  {recommandedProduct[2].name}
                </span>{' '}
                &gt;
              </div>
              <span className="desc">{recommandedProduct[2].description}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default withRouter(RecommendationWidget);
