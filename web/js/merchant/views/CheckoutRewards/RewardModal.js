import React from 'react';
import Time from 'common/ui/Time';

export const RewardModal = ({ reward, closeModal }) => {
  const {
    brand_name,
    logo,
    merchant_website_redirect_link,
    name,
    display_text,
    ends_at,
    terms,
  } = reward;

  const TIME_FORMAT = 'Do MMM YYYY';

  const renderTimeComponent = () => {
    return (
      <span className="Reward--ends-at">
        <span>Expires on: </span>
        <Time value={ends_at} format={TIME_FORMAT} />
      </span>
    );
  };

  return (
    <div className="Reward-Modal">
      <div className="media Rewards--list--item">
        <div className="media-left media-middle">
          <img src={logo} className="Reward--logo" />
        </div>
        <div className="media-body">
          <div className="media-heading Reward--brand">
            {merchant_website_redirect_link ? (
              <a href={merchant_website_redirect_link} target="_blank" rel="noreferrer noopener">
                {brand_name}
              </a>
            ) : (
              brand_name
            )}
          </div>
          <div className="Reward--desc">{name}</div>
          <div className="Reward--time-duration">{renderTimeComponent()}</div>
        </div>
      </div>
      <span className="Reward--close" onClick={closeModal}>
        <i className="i i-close" />
      </span>
      <div>
        <h1 className="Reward--headers">Reward description</h1>
        <p className="Reward--details">{display_text}</p>
      </div>
      <div>
        <h1 className="Reward--headers Reward--term-header">Terms and Conditions</h1>
        {terms.startsWith('http') ? (
          <a href={terms} target="_blank" rel="noreferrer noopener" className="Reward--term-link">
            Know more
            <i className="i i-external-link" />
          </a>
        ) : (
          <p className="Reward--details">
            {terms.split('\t').map((term) => (
              <div>{term}</div>
            ))}
          </p>
        )}
      </div>
    </div>
  );
};
