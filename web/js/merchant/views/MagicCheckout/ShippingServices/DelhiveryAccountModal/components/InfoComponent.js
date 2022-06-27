import { DELHIVERY_STEPS } from 'merchant/views/MagicCheckout/ShippingServices/constants';

const Pointer = () => (
  <div className="pointer-container">
    <div className="pointer-circle display-flex flex-center">
      <div className="inner-pointer-circle" />
    </div>
  </div>
);

const InfoComponent = () => {
  const {
    instructions: { heading, points },
  } = DELHIVERY_STEPS[0];

  return (
    <div className="delhivery-info-content">
      <div className="delhivery-account-header">How to connect:</div>
      <div className="row display-flex">
        <div className="col-sm-1 no-padding delhivery-info-highlights">
          <Pointer />
        </div>
        <div className="col-sm-11 no-padding">
          <div className="info-heading font-bold">{heading}</div>
          <div>
            <ul className="delhivery-account-list">
              {points.map((item, index) => {
                const extraClass = index === 0 ? ' no-margin-top' : '';
                return (
                  <li className={`delhivery-account-list-item ${extraClass}`} key={index}>
                    {item.custompoint ? (
                      <>
                        Send an email to the Delhivery team on{' '}
                        <span className="delhivery-info-link">de.onb@delhivery.com</span> requesting
                        for the “production authentication token”
                      </>
                    ) : (
                      item
                    )}
                  </li>
                );
              })}
            </ul>
          </div>
        </div>
      </div>
    </div>
  );
};

export default InfoComponent;
