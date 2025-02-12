import PropTypes from 'prop-types';
import Feature from 'merchant/components/Feature';
import DocsLink from 'merchant/components/DocsLink';
import Banner from './Banner';

const ProductInfo = (props) => {
  return (
    <div className="ComingSoon--ProductInfo">
      <div className="title">
        {props.title}
        <span className="badge coming-soon-badge m-l">Coming Soon!</span>
      </div>
      <div className="description">{props.description}</div>

      <div className="features-list">
        {props.features.map((data, key) => (
          <Feature {...data} key={key} />
        ))}
      </div>

      <Banner product={props.product}   interestClicked={props.interestClicked} />
    </div>
  );
};

ProductInfo.propTypes = {
  product: PropTypes.string,
  title: PropTypes.string,
  description: PropTypes.string,
  features: PropTypes.array,
};

export default ProductInfo;
