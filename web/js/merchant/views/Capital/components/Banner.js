import React from 'react';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';

const Banner = React.forwardRef(
  (
    {
      title,
      description,
      type,
      loading,
      isFormHeader = false,
      lockedNote = false,
      cta,
      rightComponent,
    },
    ref
  ) => (
    <div
      ref={ref}
      className={`loan-application-message-banner ${
        !type && isFormHeader ? 'bordered-top' : ''
      } ${
        isFormHeader ? 'application-form-header' : 'application-status-banner'
      }`}
    >
      <div
        className={`banner-message-wrapper ${type && `${type} border-left`}`}
      >
        <div className="banner-status-title-wrapper">
          {type &&
            (type === 'success' || type === 'conditional_success') && (
              <i className={`i i-check-circle ${type} status-icon`} />
            )}
          {type &&
            type === 'pending' && (
              <div class="status-icon icon-pending">
                <img src="/dist/css/assets/capital/pending.svg" />
              </div>
            )}
          {type &&
            type === 'error' && (
              <i className={`i i-info-circle ${type} status-icon`} />
            )}
          {loading ? (
            <PlaceholderLoader />
          ) : (
            <p className={'banner-status-title'}>{title}</p>
          )}
        </div>
        {loading ? (
          <PlaceholderLoader />
        ) : (
          <p
            className={`banner-status-description ${
              lockedNote ? 'locked-note-description' : ''
            }`}
          >
            {description}
          </p>
        )}
      </div>
      {cta}
      <div>
        {rightComponent}
      </div>
    </div>
  )
);

export default Banner;
