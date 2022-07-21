import { useContext } from 'react';

//Redux helper functions
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

//States
import { formContext } from './FormContext';
import { useFormikContext } from 'formik';

//Redux
import { setFormData } from 'merchant/reducers/apmForm/actions';

import { trackFormOwnerDeleted } from './analytics';

//Helper functions and constants
import { deleteOwner } from './services';
import { LOADING } from 'merchant/components/Activation/Constants';
import { OWNER_DETAILS } from './constants';

const OwnerList = ({ saveData }) => {
  const { initialValues, setInitialValues, activeOwner, setActiveOwner, setLoading } = useContext(
    formContext,
  );
  const { values, errors, dirty, validateForm } = useFormikContext();

  const reinitializeValues = (data = values) => {
    setInitialValues({ ...data });
    setTimeout(() => validateForm());
  };

  /**
   * 1. Filters out the owner from values
   * 2. Reinitializes the values with new data
   * 3. Change the active owner state if deleted owner
   * is the current owner
   * @param {*} ownerIndex - index of owner in list
   */
  const onDeleteSuccess = (ownerIndex) => {
    const filteredOwners = values[OWNER_DETAILS].filter((_, index) => index !== ownerIndex);
    const data = {
      ...values,
      [OWNER_DETAILS]: [...filteredOwners],
    };
    if (activeOwner === filteredOwners.length) setActiveOwner(activeOwner - 1);
    reinitializeValues(data);
  };

  /**
   * 1. Calls the deleteOwner api if ID exists
   * 2. Calls onDeleteSuccess anyways
   * @param {*} id - ownerId
   * @param {*} ownerIndex - Itemindex
   */
  const onDeleteOwner = async (id, ownerIndex) => {
    try {
      setLoading(LOADING.PENDING);
      if (id) await deleteOwner(id);
      onDeleteSuccess(ownerIndex);
      trackFormOwnerDeleted(id ? id : ownerIndex);
      setLoading(LOADING.SUCCESS);
    } catch (error) {
      setLoading(LOADING.ERROR);
    } finally {
      setTimeout(() => setLoading(LOADING.INITIAL), 4000);
    }
  };

  /**
   * 1. Lets the merchant switch only if current owner details are valid
   * 2. If current owner details are valid and dirty then saveData api is called.
   * @param {*} index - index of owner clicked
   */
  const onOwnerClick = async (index) => {
    if (dirty && !errors?.[OWNER_DETAILS]?.[activeOwner]) {
      const response = await saveData();
      if (response) {
        setActiveOwner(index);
      }
    }
    if (!dirty && !errors?.[OWNER_DETAILS]?.[activeOwner]) {
      setActiveOwner(index);
    }
  };

  return (
    <div className="owner-list">
      {initialValues?.[OWNER_DETAILS]?.map((owner, index) => {
        const isActive = activeOwner === index;
        const error = errors?.[OWNER_DETAILS]?.[index];
        return (
          <div className="owner-action-wrapper" key={index}>
            <div
              className={`owner-action${isActive ? ' active' : ''}${error ? ' error' : ''}`}
              onClick={() => onOwnerClick(index)}
            >
              <p className="owner-text">Owner {index + 1}</p>
              {error ? <i className="i i-info-outline" /> : null}
            </div>
            {initialValues?.[OWNER_DETAILS]?.length > 1 ? (
              <i className="i i-bin-outline" onClick={() => onDeleteOwner(owner?.id, index)} />
            ) : null}
          </div>
        );
      })}
    </div>
  );
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ setFormData }, dispatch);
};

export default connect(null, mapDispatchToProps)(OwnerList);
