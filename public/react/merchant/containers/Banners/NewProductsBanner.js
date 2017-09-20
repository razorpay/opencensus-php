import { Component } from 'react';
import { Link } from 'react-router-dom';
import Banner from 'rzp/ui/Banner';

import './NewProductsBanner.styl';

import routeSym from 'styles/assets/symbols/route.svg';
import subscriptionsSym from 'styles/assets/symbols/subscriptions.svg';
import smartCollectSym from 'styles/assets/symbols/smartcollect.svg';

const newProducts = [
  {
    name: 'Razorpay Routes',
    description: 'For Marketplace, Vendor payouts, Regional splits, etc.',
    link: '/route/payments',
    symbol: routeSym,
  },
  {
    name: 'Razorpay Subscriptions',
    description: 'Subscriptions plans with automated recurring transactions.',
    link: '/subscriptions',
    symbol: subscriptionsSym,
  },
  {
    name: 'Razorpay Smart Collect',
    description: 'Collect payments via direct bank transfers (NEFT/RGTS/IMPS).',
    link: '/virtualaccounts',
    symbol: smartCollectSym,
  },
];

const Card = ({ name, description, symbol, link }) => {
  return (
    <Link class="card col-md-4 col-xs-12" to={link}>
      <span class="symbol">
        <img src={symbol} />
      </span>
      <div class="name m-t">
        {name}
      </div>
      <p class="m-t">
        {description}
      </p>
    </Link>
  );
};

export default class NewProductsBanner extends Component {
  render() {
    return (
      <div className="NewProductsBanner">
        <Banner>
          {newProducts.map((product, index) =>
            <Card key={index} {...product} />
          )}
        </Banner>
      </div>
    );
  }
}
