const Edit = ({ switchToEdit }) => (
  <div className="native-card-edit p--16 pointer" onClick={switchToEdit}>
    <i className="i i-edit_board native-settings-edit-icon" /> Edit
  </div>
);

export default Edit;
