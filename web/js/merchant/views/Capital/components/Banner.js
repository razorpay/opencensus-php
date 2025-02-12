import React from 'react';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import SuccessTickIcon from './SuccessTickIcon';

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
    ref,
  ) => (
    <div
      ref={ref}
      className={`loan-application-message-banner ${!type && isFormHeader ? 'bordered-top' : ''} ${
        isFormHeader ? 'application-form-header' : 'application-status-banner'
      }`}
    >
      <div className={`banner-message-wrapper ${type && `${type} border-left`}`}>
        <div className="banner-status-title-wrapper">
          {type &&
            (type === 'success' || type === 'conditional_success' || type === 'approval') && (
              <div className="status-icon">
                <SuccessTickIcon
                  fill={['success', 'approval'].includes(type) ? '#24A832' : '#E79315'}
                />
              </div>
            )}
          {type && type === 'pending' && (
            <div className="status-icon icon-pending">
              <img src={require('assets/capital/pending.svg')} />
            </div>
          )}
          {type && type === 'error' && <i className={`i i-info-circle ${type} status-icon`} />}
          {loading ? <PlaceholderLoader /> : <p className={'banner-status-title'}>{title}</p>}
        </div>
        {loading ? (
          <PlaceholderLoader />
        ) : (
          <p className={`banner-status-description ${lockedNote ? 'locked-note-description' : ''}`}>
            {description}
          </p>
        )}
        {type === 'approval' && (
          <img src={require('assets/capital/green_patch.svg')} className="green_patch" />
        )}
      </div>
      {cta}
      <div>{rightComponent}</div>
    </div>
  ),
);

export default Banner;
