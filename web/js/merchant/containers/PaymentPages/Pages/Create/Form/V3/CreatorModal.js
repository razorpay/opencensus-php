import { ModalMask, Modal, ModalContent } from 'component/Modal';
import { classList } from 'common/util';

function offset(el) {
  var rect = el.getBoundingClientRect(),
    scrollLeft = window.pageXOffset || document.documentElement.scrollLeft,
    scrollTop = window.pageYOffset || document.documentElement.scrollTop;
  return { top: rect.top + scrollTop, left: rect.left + scrollLeft };
}

// TODO: Handle for field's position for top and form's position for width
function findBaseCreatorPosition() {
  const parent = document.getElementById('form-section');
  const width = parent.clientWidth + 44 * 2;

  return {
    width,
    left: offset(parent).left - 44,
  };
}

/* Position-awared Modal which opens over the field being edited */
const PositionAwaredCreator = ({ children, overWhatElement }) => {
  let style = null;

  // If not available, then opens modal in center of screen
  if (overWhatElement) {
    const creatorLayout = findBaseCreatorPosition(overWhatElement);

    style = {
      width: creatorLayout.width,
      top: '50%',
      left: creatorLayout.left,
      margin: '12px 0 0',
      transform: 'translateY(-50%)',
    };
  }

  return (
    <ModalMask maskClosable={false} class="payment-pages-v3-creator">
      <Modal
        class={classList(style && 'animate-appear')}
        showCloseBtn={false}
        style={style}
      >
        <ModalContent class="paymentlinks-creator">{children}</ModalContent>
      </Modal>
    </ModalMask>
  );
};

export default PositionAwaredCreator;
