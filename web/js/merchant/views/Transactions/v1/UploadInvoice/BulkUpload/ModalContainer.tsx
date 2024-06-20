import React, { useContext, useMemo } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import lazy from 'merchant/routes/LazyLoader';

//constants
import { ModalContextType, ModalContainerProps } from './types';

//context
import { modalContext } from './ModalContext';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

//Components
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { ModalAsideNav } from 'common/new-ui/Wizard';
import { Modal, ModalContent } from 'common/new-ui/Modal';
import Button from 'common/new-ui/Button';
import { getTabs } from './utils';

const DropScreen = lazy(() => import(/* webpackChunkName: 'DropScreen' */ './DropScreen'));
const UploadResult = lazy(() => import(/* webpackChunkName: 'UploadResult' */ './UploadResult'));
const ExitConfirmation = lazy(
  () => import(/* webpackChunkName: 'UploadResult' */ './ExitConfirmation'),
);

const ModalContainer: React.FC<ModalContainerProps> = ({
  purposeCode,
  closeModal,
  showNotification,
  refreshList,
}) => {
  const {
    currentTab,
    files,
    clientErrors,
    isUploading,
    shouldShowExitPrompt,
    setFiles,
    setCurrentTab,
    setClientErrors,
    setShouldShowExitPrompt,
  } = useContext(modalContext) as ModalContextType;

  const TABS = useMemo(() => getTabs(purposeCode), [purposeCode]);

  const onReUpload = () => {
    setFiles([]);
    setClientErrors([]);
  };

  const onTabClick = ({ target }) => {
    const tabIndex = parseInt(target.dataset.index, 10);
    if (tabIndex !== currentTab) {
      setCurrentTab(tabIndex);
      onReUpload();
    }
  };

  const onClose = () => {
    if (isUploading) {
      setShouldShowExitPrompt(true);
    } else {
      closeModal();
    }
  };

  return (
    <Modal
      className="bulk-upload-modal-wrapper Modal-container--Activation--wizard"
      onClose={onClose}
    >
      <ModalContent>
        <div className="Wizard Activation--wizard">
          <ModalAsideNav
            title="Bulk Upload"
            description={<p>Upload valid invoice and airway bill copies</p>}
            tabs={TABS}
            tabClickHandler={onTabClick}
            activeTab={currentTab}
          />
          {shouldShowExitPrompt && <ExitConfirmation closeModal={closeModal} />}
          <div className="bulk-upload-body">
            <div className="form-container Form--tabular">
              <SuspenseWithLoader>
                {files.length > 0 || clientErrors.length > 0 ? (
                  <UploadResult refreshList={refreshList} />
                ) : (
                  <DropScreen showNotification={showNotification} />
                )}
              </SuspenseWithLoader>
            </div>
            {(files.length > 0 || clientErrors.length > 0) && !isUploading && (
              <footer>
                <Button.Secondary onClick={onReUpload}>Re-Upload</Button.Secondary>
                <Button.Primary iconAfter="chevron-right device--desktop" onClick={closeModal}>
                  Continue
                </Button.Primary>
              </footer>
            )}
          </div>
        </div>
      </ModalContent>
    </Modal>
  );
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ closeModal, showNotification }, dispatch);
};

export default connect(null, mapDispatchToProps)(ModalContainer);
