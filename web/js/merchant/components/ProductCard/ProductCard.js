import './ProductCard.styl';
import React from 'react';

const primaryCTAText = 'Explore Now';
const secondaryCTAText = 'Learn More';

export default class ProductCard extends React.Component {
  onClickCTA1 = () => {
    const trackerFn = this.props.trackerFn;

    if (!trackerFn) {
      return;
    }

    window.rzpQ.push(
      trackerFn.initiated('merchant_dashboard.click_product_card_cta1', {
        // eslint-disable-next-line no-undef
        mode,
        card_text: this.props.description,
        card_id: this.props.title,
        cta_value: primaryCTAText,
        link_url: this.props.primaryLink,
        source: this.props.source,
      }),
    );
  };

  onClickCTA2 = () => {
    const trackerFn = this.props.trackerFn;

    if (!trackerFn) {
      return;
    }

    window.rzpQ.push(
      trackerFn.initiated('merchant_dashboard.click_product_card_cta2', {
        // eslint-disable-next-line no-undef
        mode,
        card_text: this.props.description,
        card_id: this.props.title,
        cta_value: secondaryCTAText,
        link_url: this.props.secondaryLink,
        source: this.props.source,
      }),
    );
  };

  render() {
    const { title, description, imgSrc, primaryLink, secondaryLink } = this.props;
    return (
      <div className="ProductCard">
        <img className="ProductCard-img" src={imgSrc} />

        <div className="ProductCard-details">
          <div className="title">{title}</div>
          <p>{description}</p>

          <div className="ProductCard-actionBtns">
            {!!primaryLink && (
              <a
                className="Button--primary--invert Button"
                href={primaryLink}
                target="_blank"
                rel="noreferrer noopener"
              >
                <b>{primaryCTAText}</b>
              </a>
            )}
            {!!secondaryLink && (
              <a
                className="Button--Link Button--transparent Button"
                href={secondaryLink}
                target="_blank"
                rel="noreferrer noopener"
              >
                <b>
                  {secondaryCTAText} <i className="i-external-link" />
                </b>
              </a>
            )}
          </div>
        </div>
      </div>
    );
  }
}
