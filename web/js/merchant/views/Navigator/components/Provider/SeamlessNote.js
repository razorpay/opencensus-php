import React, { useState, useEffect } from 'react';

import { trackOptimizerEvents } from 'merchant/views/Navigator/track';
import { stringTemplate } from 'common/utils/rzp-utils';
import { SEAMLESS_CONTENT } from 'merchant/views/Navigator/constants';

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

  return (
    <div className="feedback-card">
      <div className={`enable-seamless-info-msg ${type}`}>
        <div className="seamless-header">
          <i className="i i-info-outline" />
          <span>{SEAMLESS_CONTENT?.[selectedProvider]?.[toggleContent]?.headerText}</span>
        </div>
        <div className="seamless-desc">
          <InfoBlock
            stringReplacer={{ gatewayName }}
            list={SEAMLESS_CONTENT?.[selectedProvider]?.[toggleContent]?.infoBlock}
          />
        </div>

        <div className="seamless-how-to-block">
          <ToggleListBlock
            defaultOpen={true}
            deps={[seamlessDisabled]}
            stringReplacer={{ gatewayName }}
            buttonText={SEAMLESS_CONTENT?.[selectedProvider]?.[toggleContent]?.buttonText}
            listPoints={SEAMLESS_CONTENT?.[selectedProvider]?.[toggleContent]?.listPoints}
          />

          <p className="for-more">
            <span>For more details. Please refer to this &nbsp;</span>
            <a
              className="for-more--anchor"
              target="_blank"
              rel="noopener noreferrer"
              href={SEAMLESS_CONTENT?.[selectedProvider]?.[toggleContent]?.footerLink}
              onClick={onAnchorClick}
            >
              document <i className="i i-redirect" />
            </a>
          </p>
          {SEAMLESS_CONTENT?.[selectedProvider]?.[toggleContent]?.footerText}
        </div>
      </div>
    </div>
  );
};

export default SeamlessNote;
