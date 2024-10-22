import BenefitsIllustration from 'merchant/views/MagicCheckout/ShippingServices/assets/benefits-shiprocket.svg';
import ListBullet from 'merchant/views/MagicCheckout/ShippingServices/assets/list-bullet.svg';
import {
  BENEFITS_SHIPROCKET_HIGHLIGHTS,
  BENEFITS_SHIPROCKET_HIGHLIGHTS_INTELLIGENCE,
  BENEFITS_SHIPROCKET_HIGHLIGHTS_INTELLIGENCE_RCOD,
} from 'merchant/views/MagicCheckout/ShippingServices/constants';

const BenefitsShiprocket = ({ showIntelligenceHighlights, showRCODIntelligence }) => {
  const intelligenceHighlights = showRCODIntelligence
    ? BENEFITS_SHIPROCKET_HIGHLIGHTS_INTELLIGENCE_RCOD
    : BENEFITS_SHIPROCKET_HIGHLIGHTS_INTELLIGENCE;
  const highlights = showIntelligenceHighlights
    ? intelligenceHighlights
    : BENEFITS_SHIPROCKET_HIGHLIGHTS;
  return (
    <div className="benefits-shiprocket-container">
      {!showIntelligenceHighlights ? (
        <div className="benefits-text benefits-header font-bold color-black">
          Benefits of connecting Shiprocket account
        </div>
      ) : null}
      <div>
        {highlights.map((item, ind) => {
          const { functionality, startingText, subText, image } = item;
          return (
            <div key={ind} className="benefits-text display-flex">
              <img src={ListBullet} alt="bullet" className="benefits-shiprocket-list-icon" />
              <div className="benefits-text">
                {showIntelligenceHighlights ? (
                  <>
                    <p className="font-bold color-black">{startingText}</p>
                    <p>{subText}</p>
                    {image ? (
                      <div>
                        <img
                          src={BenefitsIllustration}
                          alt="BenefitsIllustration"
                          className="magicIntelligence-benefits-illustration"
                        />
                      </div>
                    ) : null}
                  </>
                ) : (
                  <>
                    <span>{startingText}</span>
                    <span className="font-bold color-black">{functionality}</span>
                    <span>{subText}</span>
                    {image ? (
                      <div>
                        <img src={BenefitsIllustration} alt="BenefitsIllustration" />
                      </div>
                    ) : null}
                  </>
                )}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
};

export default BenefitsShiprocket;
