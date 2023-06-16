import Popover, { PopoverBody } from 'common/ui/Popover';

const ActionComponent = ({ extraClass, item, onReview, disableAction }) => (
  <div className={`action-toolbar ${extraClass ?? ''}${disableAction ? ' disable' : ''}`}>
    <button
      type="button"
      disabled={disableAction}
      className="action-btn expire-action"
      onClick={() => onReview(item?.magic_payment_link?.id, 'expire_pl')}
      data-testid="expire-btn"
    >
      <i className="i i-timer" />
      <Popover theme="dark" className="magic-action-popover expire-action-popover">
        <PopoverBody>
          <p>Expire conversion link</p>
        </PopoverBody>
      </Popover>
    </button>
  </div>
);

export default ActionComponent;
