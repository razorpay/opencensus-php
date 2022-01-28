import BenefitsIllustration from 'merchant/views/MagicCheckout/ShippingServices/assets/benefits-shiprocket.svg';
import ListBullet from 'merchant/views/MagicCheckout/ShippingServices/assets/list-bullet.svg';
import { BENEFITS_SHIPROCKET_HIGHLIGHTS } from 'merchant/views/MagicCheckout/ShippingServices/constants';

const BenefitsShiprocket = () => (
  <div className="benefits-shiprocket-container">
    <div className="benefits-text benefits-header font-bold color-black">
      Benefits of connecting Shiprocket account
    </div>
    <div>
      {BENEFITS_SHIPROCKET_HIGHLIGHTS.map((item, ind) => (
        <div key={ind} className="benefits-text display-flex">
          <img src={ListBullet} alt="bullet" className="benefits-shiprocket-list-icon" />
          <div className="benefits-text">
            <span>{item.text1}</span>
            <span className="font-bold color-black">{item.boldText}</span>
            <span>{item.text2}</span>
            {item.image ? (
              <div>
                <img src={BenefitsIllustration} alt="BenefitsIllustration" />
              </div>
            ) : null}
          </div>
        </div>
      ))}
    </div>
  </div>
);

export default BenefitsShiprocket;
