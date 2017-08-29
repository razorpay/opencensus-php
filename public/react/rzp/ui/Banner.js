import React from 'react';
import { Link } from 'react-router-dom';

export default ({ message, cta, ...otherProps }) => {
  /*
   * @param {string} message
   * @param {function/object}
   * @returns {component}
   *
   * The component looks like bootstrap alert
   * (https://getbootstrap.com/docs/3.3/components/#alerts) , but takes an argument
   * called `cta`. when cta is a function , the function is called and the return
   * value will be embedded as cta (can be used to put components), when `cta` is a
   * dictionary, the following keys are must - "text" and one of "url" or "onClick"
   */

  let hasCta = !!cta,
    getCtaElement = null;

  const classes = ['alert', 'alert-warning'];

  if (hasCta) {
    if (typeof cta === 'function') {
      getCtaElement = cta;
    } else if (typeof cta === 'object') {
      const ctaKeys = Object.keys(cta),
        hasUrl = ctaKeys.indexOf('url') >= 0,
        hasOnClick = ctaKeys.indexOf('onClick') >= 0;
      if (!(ctaKeys.indexOf('text') >= 0) || !(hasUrl || hasOnClick)) {
        hasCta = false;
      } else {
        const props = {
          className: 'btn btn-primary',
          ...(hasOnClick && { onClick: cta.onClick }),
          ...otherProps,
        };

        getCtaElement = () =>
          hasUrl
            ? <Link to={cta.url} {...props}>
                {cta.text}
              </Link>
            : <button {...props}>
                {cta.text}
              </button>;
      }
    } else {
      hasCta = false;
    }
  }

  return (
    <div
      className={[
        'alert',
        'alert-warning',
        'rzp-banner',
        ...(hasCta && ['has-cta']),
      ].join(' ')}
    >
      <div className="rzp-banner-text">
        {message}
      </div>
      {hasCta &&
        <div className="rzp-banner-cta">
          {getCtaElement()}
        </div>}
    </div>
  );
};
