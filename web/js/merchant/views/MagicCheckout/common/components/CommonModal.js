const CommonModal = ({ infoClass, inputClass, infoComponent, inputComponent }) => {
  return (
    <div className="row display-flex">
      <div className={`col-sm-6${infoClass ?? ''}`}>{infoComponent}</div>
      <div className={`${inputClass ?? ''} col-sm-6 bg-white`}>{inputComponent}</div>
    </div>
  );
};

export default CommonModal;
