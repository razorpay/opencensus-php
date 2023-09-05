import React, { useState, useEffect } from 'react';
import { Link, ArrowUpRightIcon } from '@razorpay/blade/components';

import { stringTemplate } from 'common/utils/rzp-utils';
import { SEAMLESS_CONTENT } from 'merchant/views/Navigator/constants';
import { trackOptimizerEvents } from 'merchant/views/Navigator/track';

const InfoBlock = ({ list, stringReplacer }) => {
  if (typeof list === 'string' && !list) return null;

  if (typeof list === 'string') return stringTemplate(list, stringReplacer);

  if (React.isValidElement(list)) return list;

  return (
    <ul>
      {list.map((str, i) => (
        <li key={i}>{stringTemplate(str, stringReplacer)}</li>
      ))}
    </ul>
  );
};

const ToggleListBlock = (props) => {
  const { defaultOpen = false, deps = [], stringReplacer, buttonText, listPoints } = props;
  const [isOpen, setOpen] = useState(defaultOpen);

  const handleChange = () => setOpen(!isOpen);

  useEffect(() => {
    return () => setOpen(defaultOpen);
  }, deps);

  function renderList(listPoints) {
    if (React.isValidElement(listPoints)) return listPoints;

    return (
      <ul>
        {listPoints.map((str, i) => (
          <li key={i}>{stringTemplate(str, stringReplacer)}</li>
        ))}
      </ul>
    );
  }

  return (
    <>
      <button
        className="btn btn-text p-0 seamless-how-to"
        type="button"
        role="button"
        onClick={handleChange}
      >
        <span>{buttonText}</span>
        <img
          src="https://cdn.razorpay.com/static/assets/rewards/rewards_list_up_vector.svg"
          className={`arrow-img ${isOpen ? '' : 'arrow-img-rotate'}`}
        />
      </button>
      {isOpen && <div className="seamless-how-to-details">{renderList(listPoints)}</div>}
    </>
  );
};

const SeamlessNote = (props) => {
  const { type = 'info', seamlessDisabled, providers, selectedProvider, isEdit } = props;

  const gatewayName = providers?.[selectedProvider]?.['Gateway Name']?.data_value;
  const toggleContent = seamlessDisabled ? 'disable' : 'enable';

  const onAnchorClick = () => {
    trackOptimizerEvents({
      screen: `Optimizer ${isEdit ? 'Edit' : 'Add'} Provider`,
      objectName: 'Know more',
      actionName: 'click',
      properties: {
        gateway: selectedProvider,
        'Integration Type': seamlessDisabled ? 'Instant (beta)' : 'Server-to-Server',
      },
    });
  };

  const { headerText, infoBlock, buttonText, listPoints, notePoints, footerLink, footerText } =
    SEAMLESS_CONTENT?.[selectedProvider]?.[toggleContent] || {};

  return (
    <div className="feedback-card">
      <div className={`enable-seamless-info-msg ${type}`}>
        <div className="seamless-header">
          <i className="i i-info-outline" />
          <span>{headerText}</span>
        </div>
        <div className="seamless-desc">
          <InfoBlock stringReplacer={{ gatewayName }} list={infoBlock} />
        </div>

        <div className="seamless-how-to-block">
          <ToggleListBlock
            defaultOpen={true}
            deps={[seamlessDisabled]}
            stringReplacer={{ gatewayName }}
            buttonText={buttonText}
            listPoints={listPoints}
          />

          {notePoints?.length > 0 ? (
            <>
              <h5 className="notes-header">Notes:</h5>
              <ul>
                {notePoints.map((item, index) => (
                  <li key={index}>{item}</li>
                ))}
              </ul>
            </>
          ) : null}

          {footerLink ? (
            <p className="for-more">
              <span>For more details. Please refer to this &nbsp;</span>
              <Link
                href={footerLink}
                onClick={onAnchorClick}
                icon={ArrowUpRightIcon}
                iconPosition="right"
                rel="noreferrer noopener"
                target="_blank"
                variant="anchor"
                size="small"
              >
                document
              </Link>
            </p>
          ) : null}
          {footerText}
        </div>
      </div>
    </div>
  );
};

export default SeamlessNote;
