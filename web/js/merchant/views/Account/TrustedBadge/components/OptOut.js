import React from 'react';
import { AsyncBtn as Button } from 'common/new-ui/Button';
import Modal from 'react-modal';

import { optOutDialog } from '../constants/data';

const OptOut = (props) => {
  const modalState = props.state || {};
  const data = optOutDialog[modalState.type] || {};
  const loading = Boolean(modalState.loading);

  React.useEffect(() => {
    if (props.state && props.state.show) {
      props.trackEvent('RTBOptOutPopupRendered');
    }
  }, [props]);

  const handleAction = React.useCallback(
    (actionData) => {
      if (typeof props.handleAction === 'function') {
        props.handleAction(actionData.action);
      }
    },
    [props],
  );
  return (
    <Modal
      isOpen={modalState.show}
      onRequestClose={props.onAbort}
      closeTimeoutMS={300}
      className="Modal Modal-rtb--small Modal--confirm"
      contentLabel="ConfirmModal"
      ariaHideApp={false}
    >
      <div className="modal-header">
        <h3 className="modal-title">{data.title}</h3>
      </div>

      <div className="modal-body">
        <p>{data.body}</p>
        {data.link && (
          <a
            href={data.link.href}
            className="link-color mt-2"
            rel="noreferrer noopener"
            target="_blank"
          >
            {data.link.text} <i className="new-window-icon" />
          </a>
        )}
        {data.action && (
          <div className="rtb-modal-action">
            {data.action.map((actionData) => {
              const onClick = () => handleAction(actionData);
              if (actionData.type === 'primary') {
                return (
                  <Button.Primary onClick={onClick} key={actionData.label} isPending={loading}>
                    {actionData.label}
                  </Button.Primary>
                );
              }
              return (
                <Button disabled={loading} onClick={onClick} key={actionData.label}>
                  {actionData.label}
                </Button>
              );
            })}
          </div>
        )}
      </div>
    </Modal>
  );
};

export default OptOut;
