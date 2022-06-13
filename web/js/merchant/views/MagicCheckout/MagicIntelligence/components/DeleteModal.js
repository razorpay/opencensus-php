import { AsyncBtn } from 'common/new-ui/Button';
import { connect } from 'react-redux';

const DeleteModal = ({ closeModal, id, deleteItem, modalSource, blocklist, allowlist }) => {
  return (
    <>
      <div className="modal-header">
        <h3 className="modal-title">{`Are you sure want to delete the item from ${modalSource}?`}</h3>
      </div>
      <div className="modal-body">
        <div className="Modal__actions">
          <AsyncBtn type="button" className="btn btn-outline" onClick={closeModal}>
            Cancel
          </AsyncBtn>
          <AsyncBtn.Primary
            type="button"
            isPending={modalSource === 'Blocklist' ? blocklist.loading : allowlist.loading}
            onClick={() => {
              deleteItem(id);
            }}
            className="btn btn-primary"
          >
            Yes, Delete
          </AsyncBtn.Primary>
        </div>
      </div>
    </>
  );
};

const mapStateToProps = (state) => {
  return { blocklist: { ...state.magicBlocklist }, allowlist: { ...state.magicAllowlist } };
};

export default connect(mapStateToProps, null)(DeleteModal);
