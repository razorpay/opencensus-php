import React, { useState } from 'react';
import Time from 'common/ui/Time';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Popover, { PopoverBody } from 'common/ui/Popover';

const TIME_FORMAT = 'Do MMM';

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
    logo,
    name,
    display_text,
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

  const CTAButton = () =>
    CTA[status] == 'Remove' ? (
      status == 'live' ? (
        <div
          className={`Rewards--button-grp ${isEditing ? 'Rewards--button-grp--extra-width' : ''}`}
        >
          <AsyncBtn.Transparent
            class={`Reward--remove-button ${isEditing ? 'Reward--remove-button--disabled' : ''}`}
            onClick={() => clickRemove()}
            disabled={false}
            showLoader={false}
            pendingState="Removing..."
          >
            {isEditing ? 'Removing...' : CTA[status]}
          </AsyncBtn.Transparent>
          <Button.Primary
            type="button"
            class={status == 'live' ? 'Reward--live-button' : 'Reward--next-button'}
          >
            {status == 'live' ? 'LIVE' : 'NEXT'}
          </Button.Primary>
        </div>
      ) : (
        <AsyncBtn.Transparent
          class={`Reward--remove-button ${isEditing ? 'Reward--remove-button--disabled' : ''}`}
          onClick={() => clickRemove()}
          disabled={false}
          showLoader={false}
          pendingState="Removing..."
        >
          {isEditing ? 'Removing...' : CTA[status]}
        </AsyncBtn.Transparent>
      )
    ) : (
      <>
        <AsyncBtn.Primary
          class={`Button--small Reward--activate-button ${
            isEditing ? 'Reward--activate-button--disabled' : ''
          }`}
          disabled={false}
          onClick={() => clickActive()}
          showLoader={false}
          pendingState="Activating..."
        >
          {isEditing ? 'Activating...' : CTA[status]}
        </AsyncBtn.Primary>
        {!props.isOneRewardLive && (
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
    if (props.subsection === SUB_SECTIONS.AVAILABLE_NOW) {
      return (
        <span className="Reward--ends-at">
          <span>Expires on: </span>
          <Time value={ends_at} format={TIME_FORMAT} />
        </span>
      );
    } else {
      return (
        <>
          <span className="Reward--starts-at">
            {status === 'live' ? 'Live: ' : 'From '}
            <Time value={starts_at} format={TIME_FORMAT} />
          </span>
          <span className="Reward--ends-at">
            {status === 'live' ? 'Expires: ' : 'to '}
            <Time value={ends_at} format={TIME_FORMAT} />
          </span>
        </>
      );
    }
  };

  return (
    <div className="media Rewards--list--item">
      <div className="media-left media-middle">
        <img src={logo} className="Reward--logo" />
      </div>
      <div className="media-body">
        <div className="media-heading Reward--brand">
          {merchant_website_redirect_link ? (
            <a href={merchant_website_redirect_link} target="_blank" rel="noreferrer noopener">
              {name}
            </a>
          ) : (
            name
          )}
        </div>
        <div className="Reward--desc">{display_text}</div>
        <div className="Reward--time-duration">{renderTimeComponent()}</div>
      </div>
      <div className="media-right">
        <CTAButton />
      </div>
    </div>
  );
};

export default React.memo(Reward);
