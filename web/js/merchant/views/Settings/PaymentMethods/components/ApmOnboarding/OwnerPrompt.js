import { useContext } from 'react';

//States
import { formContext } from './FormContext';
import { useFormikContext } from 'formik';

//Constants
import { OWNER_DETAILS, ownershipFormValues, ownerCountList } from './constants';
import { deleteOwner } from './services';
import { LOADING } from 'merchant/components/Activation/Constants';

//Components
import Input from 'common/new-ui/Input';

const OwnerPrompt = ({ saveData }) => {
  const {
    setInitialValues,
    ownerCount,
    setOwnerCount,
    setLoading,
    activeOwner,
    setActiveOwner,
  } = useContext(formContext);
  const { values, errors, dirty, validateForm } = useFormikContext();

  /**
   * This adds the extra owners required by finiding the difference between the current owner count
   * and the selected owner count
   * @param {*} ownerCount = count from dropdown
   * @returns {Object} formdata object
   */
  const addOwners = (ownerCount) => {
    const owners = ownerCount - (values?.[OWNER_DETAILS]?.length ?? 0);
    const newOwner = { ...ownershipFormValues };
    const data = {
      ...values,
      [OWNER_DETAILS]: [...values.owner_details, ...Array(owners).fill(newOwner)],
    };
    return data;
  };

  /**
   * This calls the delete owner api to delete owners that already have an id attached to them
   * when the dropdown is set to a number below the current owner count
   * @param {*} ownerCount = count from dropdown
   * @returns {Object} formdata object
   */
  const removeOwners = async (ownerCount) => {
    const data = { ...values, [OWNER_DETAILS]: [] };
    const deletedOwners = [];
    values?.[OWNER_DETAILS].forEach((owner, index) => {
      if (index + 1 > ownerCount && owner?.id) {
        deletedOwners.push(deleteOwner(owner.id));
      }
      if (index + 1 <= ownerCount) {
        data?.[OWNER_DETAILS].push(owner);
      }
    });
    if (data?.[OWNER_DETAILS]?.length === 0) {
      data?.[OWNER_DETAILS].push({ ...ownershipFormValues });
    }
    await Promise.all(deletedOwners);
    return data;
  };

  const saveOwnerData = async (data, owners) => {
    if (dirty && !errors?.[OWNER_DETAILS]?.[activeOwner] && activeOwner + 1 <= owners) {
      await saveData(data);
    } else {
      setInitialValues({ ...data });
      setTimeout(() => validateForm());
    }
  };

  /**
   * Handles on change even for owner count selection dropdown, calls addOwner/removeOwner depending upon
   * the count selected
   * @param {*} event = default param from dropdown
   */
  const onChange = async (event) => {
    try {
      const value = parseInt(event.currentTarget.value, 10);
      let data;
      if (value > (values?.[OWNER_DETAILS]?.length ?? 0)) {
        data = addOwners(value);
      } else {
        setLoading(LOADING.PENDING);
        data = await removeOwners(value);
        setLoading(LOADING.SUCCESS);
      }
      await saveOwnerData(data, value);
      setOwnerCount(value);
      if (activeOwner > value - 1) setActiveOwner(0);
    } catch {
      setLoading(LOADING.ERROR);
    } finally {
      setTimeout(() => setLoading(LOADING.INITIAL), 4000);
    }
  };

  return (
    <div className={`owner-prompt${ownerCount ? ' selected' : ''}`}>
      <p className="prompt">
        How many owners in your company have ownership greater than or equal to 25%?
      </p>
      <Input.Select options={ownerCountList} onChange={onChange} value={ownerCount} />
    </div>
  );
};

export default OwnerPrompt;
