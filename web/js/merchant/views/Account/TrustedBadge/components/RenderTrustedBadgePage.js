import React from 'react';
import PropTypes from 'prop-types';
import { AsyncBtn as Button } from 'common/new-ui/Button';

import OptOut from './OptOut';
import Growth from './Growth';
import Details from './Details';
import SubText from './SubText';
import { mergeComponent, sortBy } from '../helper';

const RenderTrustedBadgePage = ({
  status,
  data,
  trackEvent,
  updateStatus,
  loading,
  updateAction,
}) => {
  // Opt Out Modal State
  const [modalState, setModalState] = React.useState({ show: false });

  const selectedData = React.useMemo(() => data[status], [data, status]);
  const components = React.useMemo(() => mergeComponent(selectedData, data.components), [
    data,
    selectedData,
  ]);
  const componentOrder = React.useMemo(() => sortBy(components, selectedData.order), [
    components,
    selectedData,
  ]);

  const handleOptOut = React.useCallback(
    (event) => {
      event.preventDefault();
      if (event.target.id === 'rtbOptOut') {
        trackEvent('RTBOptOutOptionClicked');
        setModalState({ show: true, type: 'confirm' });
      }
    },
    [trackEvent],
  );

  /**
   * To switch optout dialog to postConfirm dialog
   */
  React.useEffect(() => {
    if (
      modalState.show === true &&
      modalState.type === 'confirm' &&
      loading === false &&
      updateAction === 'optout'
    ) {
      setModalState({ show: true, type: 'postConfirm', loading: false });
    }
  }, [updateAction, loading, modalState]);

  const handleAction = React.useCallback(
    (eventName) => {
      switch (eventName) {
        case 'close-modal':
          setModalState({ show: false });
          break;
        case 'opt-out':
          updateStatus('optout');
          trackEvent('RTBOptOutConfirmationClicked');
          setModalState({ show: true, type: 'confirm', loading: true });
          break;
        case 'activate-badge':
          updateStatus('optin');
          trackEvent('RTBActivated');
          break;
        case 'join-waitlist':
          updateStatus('waitlist');
          trackEvent('RTBWaitlisted');
          break;
        default:
          break;
      }
    },
    [updateStatus, trackEvent],
  );

  const renderComponent = React.useCallback(
    ({ type, ...componentData }) => {
      switch (type) {
        case 'button':
          return (
            <div key={componentData.id} className="action-item">
              <Button.Primary
                onClick={() => handleAction(componentData.action)}
                isPending={loading}
                pendingState={componentData.label}
              >
                {componentData.label}
              </Button.Primary>
              <div className="helper-text">{componentData.helperText}</div>
            </div>
          );
        case 'growth':
          return <Growth key={componentData.id} {...componentData} />;
        case 'details':
          return (
            <Details
              key={componentData.id}
              {...componentData}
              handleSubComponent={(id) => renderComponent(components[id])}
            />
          );
        case 'sub-text':
          return (
            <SubText
              key={componentData.id}
              {...componentData}
              handleOptOut={handleOptOut}
              trackEvent={trackEvent}
            />
          );
        case 'text-icon':
          return (
            <div key={componentData.id} className="text-icon">
              {componentData.icon && <img alt={componentData.text} src={componentData.icon} />}
              {componentData.text}
            </div>
          );
        case 'divider':
          return <div key={componentData.id} className="rtb-divider" />;
        default:
          return null;
      }
    },
    [components, handleOptOut, trackEvent, loading, handleAction],
  );

  return (
    <>
      <OptOut
        onAbort={() => setModalState({ show: false })}
        state={modalState}
        handleAction={handleAction}
        trackEvent={trackEvent}
      />
      {componentOrder.map((singleComponent) => renderComponent(singleComponent))}
    </>
  );
};

RenderTrustedBadgePage.propTypes = {
  status: PropTypes.string.isRequired,
  data: PropTypes.any.isRequired,
  trackEvent: PropTypes.func.isRequired,
  updateStatus: PropTypes.func.isRequired,
  loading: PropTypes.bool,
  updateAction: PropTypes.string,
};

export default RenderTrustedBadgePage;
