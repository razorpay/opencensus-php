import { Component } from 'react';
import { Link } from 'react-router-dom';
import Banner from 'rzp/ui/Banner';
import newProducts from 'merchant/containers/Banners/newProducts';

import './NewProductsBanner.styl';

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
      <div class="btn-link action-btn">Get Started ></div>
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
