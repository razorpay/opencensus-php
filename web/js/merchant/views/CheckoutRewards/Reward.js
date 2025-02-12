import React, { useState } from 'react';
import Time from 'common/ui/Time';
import { AsyncBtn } from 'common/new-ui/Button';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { RewardModal } from './RewardModal';

const TIME_FORMAT = 'Do MMM YYYY';

export const STATUSES = {
  AVAILABLE: 'available',
  LIVE: 'live',
  QUEUE: 'queue',
  EXPIRED: 'expired',
};

export const SECTIONS = {
  AVAILABLE: 'available',
  LIVE: 'live',
  QUEUE: 'queue',
};

export const SUB_SECTIONS = {
  AVAILABLE_NOW: 'available-now',
  AVAILABLE_LATER: 'available-later',
  LIVE: 'live',
  QUEUE: 'queue',
};

const CTA = {
  available: 'Activate',
  live: 'Remove',
  queue: 'Remove',
};

const Reward = (props) => {
  const {
    id,
    brand_name,
    logo,
    name,
    starts_at,
    ends_at,
    status,
    merchant_website_redirect_link,
  } = props.reward;

  const [isEditing, setIsEditing] = useState(false);

  const clickActive = () => {
    return new Promise((resolve, reject) => {
      setIsEditing(true);
      props
        .checkForQueue(id, status, starts_at, ends_at)
        .then(() => {
          setIsEditing(false);
          resolve();
        })
        .catch(() => {
          setIsEditing(false);
          reject();
        });
    });
  };

  const clickRemove = () => {
    return new Promise((resolve, reject) => {
      setIsEditing(true);
      props
        .editStatus(id, status)
        .then(() => {
          setIsEditing(false);
          resolve();
        })
        .catch(() => {
          setIsEditing(false);
          reject();
        });
    });
  };

  const openTerms = () => {
    props.openModal({
      component: <RewardModal reward={props.reward} closeModal={props.closeModal} />,
      size: 'large',
    });
  };

  const CTAButton = () =>
    CTA[status] == 'Remove' ? (
      <AsyncBtn.Transparent
        className={`Reward--remove-button ${isEditing ? 'Reward--remove-button--disabled' : ''}`}
        onClick={clickRemove}
        disabled={false}
        showLoader={false}
        pendingState="Removing..."
      >
        {isEditing ? 'Removing...' : CTA[status]}
      </AsyncBtn.Transparent>
    ) : (
      <>
        <AsyncBtn.Primary
          className={`Button--small Reward--activate-button ${
            isEditing ? 'Reward--activate-button--disabled' : ''
          }`}
          disabled={props.isEmailAndContactOptional}
          onClick={clickActive}
          showLoader={false}
          pendingState="Activating..."
        >
          {isEditing ? 'Activating...' : CTA[status]}
        </AsyncBtn.Primary>
        {!props.isEmailAndContactOptional && !props.isOneRewardLive && (
          <Popover align="bottom" theme="dark" className="reward-active-button-popover">
            <PopoverBody>
              <div>
                <div>This reward will be activated on your checkout.</div>
                <div>No worries ! You can remove it anytime.</div>
              </div>
            </PopoverBody>
          </Popover>
        )}
      </>
    );

  const renderTimeComponent = () => {
    if (props.subsection === SUB_SECTIONS.AVAILABLE_NOW || props.subsection === SUB_SECTIONS.LIVE) {
      return (
        <span className="Reward--ends-at">
          <span>Expires: </span>
          <Time value={ends_at} format={TIME_FORMAT} />
        </span>
      );
    } else {
      return (
        <>
          <span className="Reward--starts-at">
            {'Starts: '}
            <Time value={starts_at} format={TIME_FORMAT} />
          </span>
          <span className="Reward--ends-at">
            {'Expires: '}
            <Time value={ends_at} format={TIME_FORMAT} />
          </span>
        </>
      );
    }
  };

  return (
    <div className={`media Rewards--list--item Rewards--list--item-${props.subsection}`}>
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
        <div className="Reward--time-duration">
          {renderTimeComponent()}
          <span className="Reward--terms" onClick={openTerms}>
            {'T&C'}
          </span>
        </div>
      </div>
      <div className="media-right">
        <CTAButton />
      </div>
    </div>
  );
};

export default React.memo(Reward);
