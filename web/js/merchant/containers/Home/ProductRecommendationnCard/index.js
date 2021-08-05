import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { withRouter } from 'react-router-dom';
import { fetchPaymentLinks } from 'merchant/reducers/paymentlinks/list';
import { fetchKeys } from 'merchant/reducers/keys';
import { fetchPaymentPagesList } from 'merchant/views/PaymentPages/PaymentPages/model';
import { fetchPaymentButtonsList as fetchPaymentButton } from 'merchant/reducers/paymentbuttons/list';
import { fetchSubscriptionButtonsList as fetchSubscriptionButton } from 'merchant/reducers/subscriptionButtons/list';
import { fetchPayments } from 'merchant/reducers/collection';
import { getRecommendedProduct } from './productMap';

const RecommendationWidget = ({
  fetchPaymentsLinks,
  fetchPaymentButtonsList,
  fetchSubscriptionButtonsList,
  fetchPayment,
  fetchKey,
  user,
  mode,
  history,
}) => {
  const [data, setData] = useState([]);
  const landingProduct = localStorage.getItem('merchant_landing_page');
  const recommandedProduct = getRecommendedProduct(landingProduct);

  const getPaymentLink = async () => {
    const paymentLinks = await fetchPaymentsLinks({ count: 1 });
    if (paymentLinks?.data?.items) {
      setData(paymentLinks.data.items);
    }
  };

  const getApiKeys = async () => {
    const params = { mode };
    const keys = await fetchKey(params, user.has_key_access);
    if (keys?.data?.items) {
      setData(keys.data.items);
    }
  };

  const fetchPaymentPage = async () => {
    const paymentPage = await fetchPaymentPagesList({ count: 1 });
    if (paymentPage?.data?.items) {
      setData(paymentPage.data.items);
    }
  };

  const getPaymentButtons = async () => {
    const paymentButton = await fetchPaymentButtonsList({ count: 1 });
    const subscriptionButton = await fetchSubscriptionButtonsList({ count: 1 });
    if (paymentButton?.data?.items) {
      setData(paymentButton.data.items);
    } else if (subscriptionButton?.data?.items) {
      setData(subscriptionButton.data.items);
    }
  };

  const getPayments = async (parms) => {
    const payments = await fetchPayment(parms);
    return payments;
  };

  const fetchSmartCollectList = async () => {
    const params = { count: 1, virtual_account: 1 };
    const smartCollectPayment = await getPayments(params);
    if (smartCollectPayment?.data?.items) {
      setData(smartCollectPayment.data.items);
    }
  };

  const getRoutePaymentsList = async () => {
    const params = { count: 1, transferred: 1 };
    const routeList = await getPayments(params);
    if (routeList?.data?.items) {
      setData(routeList.data.items);
    }
  };

  const getSubscriptionList = async () => {
    const params = { count: 1, recurring: 1 };
    const subscriptionList = await getPayments(params);
    if (subscriptionList?.data?.items) {
      setData(subscriptionList.data.items);
    }
  };

  useEffect(() => {
    if (landingProduct && recommandedProduct) {
      switch (landingProduct) {
        case 'payment_link':
          getPaymentLink();
          break;
        case 'payment_gateway':
          getApiKeys();
          break;
        case 'payment_page':
          fetchPaymentPage();
          break;
        case 'payment_button':
          getPaymentButtons();
          break;
        case 'smart_collect':
          fetchSmartCollectList();
          break;
        case 'route':
          getRoutePaymentsList();
          break;
        case 'subscriptions':
          getSubscriptionList();
          break;
        default:
          break;
      }
    }
  }, []);

  if (!landingProduct || !recommandedProduct) {
    return null;
  }

  if (data?.length) {
    localStorage.removeItem('merchant_landing_page');
  }

  return (
    <div className="recommendation-widget">
      <div className="recommend-product">
        <div className="title">Get started with Razorpay product suite</div>
        <div className="active-product">
          <img src={recommandedProduct[0].imageCdn} />
          <div className="active-product__container">
            <div className="name">
              <span onClick={() => history.push(recommandedProduct[0].redirectUrl)}>
                {recommandedProduct[0].name}
              </span>{' '}
              &gt;
            </div>
            <div>{recommandedProduct[0].description}</div>
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
                <span onClick={() => history.push(recommandedProduct[1].redirectUrl)}>
                  {recommandedProduct[1].name}
                </span>{' '}
                &gt;
              </div>
              <span>{recommandedProduct[1].description}</span>
            </div>
          </div>
          <div className="prd-two">
            <img src={recommandedProduct[2].imageCdn} />
            <div className="container">
              <div className="name">
                <span onClick={() => history.push(recommandedProduct[2].redirectUrl)}>
                  {recommandedProduct[2].name}
                </span>{' '}
                &gt;
              </div>
              <span>{recommandedProduct[2].description}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default compose(
  withRouter,
  connect(null, {
    fetchPaymentsLinks: fetchPaymentLinks,
    fetchKey: fetchKeys,
    fetchPaymentButtonsList: fetchPaymentButton,
    fetchSubscriptionButtonsList: fetchSubscriptionButton,
    fetchPayment: fetchPayments,
  }),
)(RecommendationWidget);
