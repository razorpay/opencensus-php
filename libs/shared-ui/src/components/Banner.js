import React from 'react';
import { Link } from 'react-router-dom';

/*
 * Input:
 * @param {String} message
 * @param {Function/Object} cta
 *
 * Description:
 * The component looks like bootstrap alert
 * (https://getbootstrap.com/docs/3.3/components/#alerts) , but takes an argument
 * called `cta`(Call to Action). when cta is a function , the function is called
 * and the return value will be embedded as cta (can be used to put components),
 * when `cta` is a string( cta text) -
 * one of "ctaUrl" or "ctaOnClick", otherwise cta will not be displayed
 *
 * Example:
 * <Banner message="my message" cta="Action" ctaUrl="http://example.com/"/>
 */

// eslint-disable-next-line react/display-name
export default ({ children, cta, ctaUrl, ctaOnClick, className }) => {
  let getCtaElement = null;
  let hasCta = true;

  const hasUrl = !!ctaUrl;
  const hasOnClick = !!ctaOnClick && typeof ctaOnClick === 'function';

  hasCta = !!cta && (hasUrl || hasOnClick);

  if (hasCta) {
    if (typeof cta === 'function') {
      getCtaElement = cta;
    } else if (typeof cta === 'string') {
      const props = {
        className: 'btn btn-primary',
        ...(hasOnClick && { onClick: ctaOnClick }),
      };

      getCtaElement = () =>
        hasUrl ? (
          <Link to={ctaUrl} {...props}>
            {cta}
          </Link>
        ) : (
          <button {...props}>{cta}</button>
        );
    } else {
      hasCta = false;
    }
  }

  const classNames = ['alert', 'alert-warning', 'rzp-banner', className];

  return (
    <div className={classNames.join(' ')}>
      <div className="rzp-banner-text">{children}</div>
      {hasCta ? <div className="rzp-banner-cta">{getCtaElement()}</div> : null}
    </div>
  );
};
