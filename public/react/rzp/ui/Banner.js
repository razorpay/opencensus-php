import React from 'react';
import { Link } from 'react-router-dom';

export default ({ message, cta, ...otherProps }) => {
  /*
   * @param {string} message
   * @param {function/object} cta
   *
   * The component looks like bootstrap alert
   * (https://getbootstrap.com/docs/3.3/components/#alerts) , but takes an argument
   * called `cta`(Call to Action). when cta is a function , the function is called and the return
   * value will be embedded as cta (can be used to put components), when `cta` is a
   * dictionary, the following keys are must - "text" and one of "url" or "onClick",
   * otherwise cta will not be displayed
   */

  let hasCta = !!cta,
    getCtaElement = null;

  if (hasCta) {
    if (typeof cta === 'function') {
      getCtaElement = cta;
    } else if (typeof cta === 'object') {
      const hasUrl = cta.hasOwnProperty('url'),
        hasOnClick =
          cta.hasOwnProperty('onClick') && typeof cta.onClick === 'function';

      if (!cta.hasOwnProperty('text') || !(hasUrl || hasOnClick)) {
        hasCta = false;
      } else {
        const props = {
          className: 'btn btn-primary',
          ...(hasOnClick && { onClick: cta.onClick }),
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
