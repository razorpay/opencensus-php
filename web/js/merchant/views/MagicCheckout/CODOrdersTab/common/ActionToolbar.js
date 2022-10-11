import Popover, { PopoverBody } from 'common/ui/Popover';
import trashOutline from 'assets/trash-outline.svg';
import { REVIEW_ACTIONS } from 'merchant/views/MagicCheckout/CODOrdersTab/constants';

const ActionToolbar = ({ extraClass, item, onReview, disableActions, hideHold }) => (
  <div className={`action-toolbar${extraClass ?? ''}${disableActions ? ' disable' : ''}`}>
    <button
      type="button"
      disabled={disableActions}
      className="action-btn approve-action"
      onClick={() => onReview(item?.id, REVIEW_ACTIONS.approve)}
    >
      <i className="i i-approve" />
      <Popover persistent={false} theme="dark" className="magic-action-popover">
        <PopoverBody>
          <p>Approve Order</p>
        </PopoverBody>
      </Popover>
    </button>
    <button
      type="button"
      disabled={disableActions}
      className="action-btn cancel-action"
      onClick={() => onReview(item?.id, REVIEW_ACTIONS.cancel)}
    >
      <img src={trashOutline} alt="trash-outline" className="trash-outline" />
      <Popover persistent={false} theme="dark" className="magic-action-popover">
        <PopoverBody>
          <p>Cancel Order</p>
        </PopoverBody>
      </Popover>
    </button>
    {!hideHold ? (
      <button
        type="button"
        disabled={disableActions}
        className="action-btn hold-action"
        onClick={() => onReview(item?.id, REVIEW_ACTIONS.hold)}
      >
        <i className="i i-hold" />
        <Popover persistent={false} theme="dark" className="magic-action-popover">
          <PopoverBody>
            <p>Hold Order</p>
          </PopoverBody>
        </Popover>
      </button>
    ) : null}
  </div>
);

export default ActionToolbar;
