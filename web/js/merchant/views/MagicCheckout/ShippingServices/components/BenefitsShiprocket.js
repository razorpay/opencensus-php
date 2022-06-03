import BenefitsIllustration from 'merchant/views/MagicCheckout/ShippingServices/assets/benefits-shiprocket.svg';
import ListBullet from 'merchant/views/MagicCheckout/ShippingServices/assets/list-bullet.svg';
import {
  BENEFITS_SHIPROCKET_HIGHLIGHTS,
  BENEFITS_SHIPROCKET_HIGHLIGHTS_INTELLIGENCE,
} from 'merchant/views/MagicCheckout/ShippingServices/constants';

const BenefitsShiprocket = ({ showIntelligenceHighlights }) => {
  const highlights = showIntelligenceHighlights
    ? BENEFITS_SHIPROCKET_HIGHLIGHTS_INTELLIGENCE
    : BENEFITS_SHIPROCKET_HIGHLIGHTS;
  return (
    <div className="benefits-shiprocket-container">
      <div className="benefits-text benefits-header font-bold color-black">
        Benefits of connecting Shiprocket account
      </div>
      <div>
        {highlights.map((item, ind) => {
          const { functionality, startingText, subText, image } = item;
          return (
            <div key={ind} className="benefits-text display-flex">
              <img src={ListBullet} alt="bullet" className="benefits-shiprocket-list-icon" />
              <div className="benefits-text">
                <span>{startingText}</span>
                <span className="font-bold color-black">{functionality}</span>
                <span>{subText}</span>
                {image ? (
                  <div>
                    <img src={BenefitsIllustration} alt="BenefitsIllustration" />
                  </div>
                ) : null}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default BenefitsShiprocket;
