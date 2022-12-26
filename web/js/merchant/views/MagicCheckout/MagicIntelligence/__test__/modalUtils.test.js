import {
  openConfirmDeleteModal,
  openBatchUploadModal,
} from 'merchant/views/MagicCheckout/MagicIntelligence/modal_util';

const openModal = jest.fn();
const closeModal = jest.fn();
const id = 'JSy90d9ndDOFK';
const deleteItem = jest.fn();
const modalSource = 'delete';
const createBatch = jest.fn();
const validateBatch = jest.fn();
const validateListModalInfo = jest.fn();
const onModalClose = jest.fn();
const uploadList = jest.fn();
const fetchAll = jest.fn();
const resetHandler = jest.fn();
const closeSuccessModal = jest.fn();
const gaEvents = jest.fn();
const sampleFile = 'https://test.cdn.razorpay.sample';
const ctaText = 'helo';
const title = 'title';
const batchType = 'type';
const successText = 'success';
const list = ['email', 'phone'];

describe('testing utils', () => {
  test('should call open modal when calling confirm delete modal function', () => {
    openConfirmDeleteModal(openModal, closeModal, id, deleteItem, modalSource);
    expect(openModal).toHaveBeenCalled();
  });

  test('should call open modal when calling batch upload modal function', () => {
    openBatchUploadModal(
      createBatch,
      validateBatch,
      openModal,
      onModalClose,
      uploadList,
      fetchAll,
      resetHandler,
      closeSuccessModal,
      gaEvents,
      sampleFile,
      validateListModalInfo,
      ctaText,
      title,
      batchType,
      successText,
      list,
      modalSource,
    );
    expect(openModal).toHaveBeenCalled();
  });
});
