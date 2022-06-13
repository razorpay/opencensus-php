import DeleteModal from 'merchant/views/MagicCheckout/MagicIntelligence/components/DeleteModal';
import BatchUpload from 'merchant/containers/BatchNew/Upload';
import ManualUpload from 'merchant/views/MagicCheckout/MagicIntelligence/components/ManualUpload';

export const openConfirmDeleteModal = (openModal, closeModal, id, deleteItem, modalSource) => {
  openModal({
    size: 'small',
    component: (
      <DeleteModal
        closeModal={closeModal}
        id={id}
        deleteItem={deleteItem}
        modalSource={modalSource}
      />
    ),
  });
};

export const openBatchUploadModal = (...args) => {
  const [
    createBatch,
    validateBatch,
    openModal,
    onModalClose,
    uploadList,
    fetchAll,
    resetHandler,
    closeSuccessModal,
    gaEvents,
    SAMPLE_FILE,
    validateListModalInfo,
    ctaText,
    title,
    batchType,
    successText,
    list,
    modalSource,
  ] = args;

  openModal({
    size: 'large',
    component: (
      <BatchUpload
        accept={['csv']}
        sampleUrl={SAMPLE_FILE}
        closeUrl="/magic/settings"
        ctaText={ctaText}
        title={title}
        pendingText="Uploading..."
        batchType={batchType}
        maxRows={1000000} // 1M (Excel Limit)
        createBatch={createBatch}
        validateBatch={validateBatch}
        gaEvents={gaEvents}
        validateModalInfo={validateListModalInfo('1M', SAMPLE_FILE)}
        maxFileSize={52428800} // 50MB
        successText={successText}
        batchListClass="list-upload"
        modalType="list-upload"
        closeSuccessModal={closeSuccessModal}
        component={
          <ManualUpload
            list={list}
            onModalClose={onModalClose}
            uploadList={uploadList}
            fetchAll={fetchAll}
            modalSource={modalSource}
            resetHandler={resetHandler}
          />
        }
      />
    ),
  });
};
