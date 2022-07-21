import { merchantFetch } from 'merchant/utils/ajax';
import { OWNER_DETAILS } from './constants';

/**
 * Function to fetch form data
 * @returns {null} object (response, error)
 */
export const fetchFormData = () => {
  return merchantFetch({
    url: 'merchant/international/apm_request',
    method: 'get',
  });
};

/**
 * Callback function is called to update the data in redux
 * Owner details are returned if present
 * @param {*} data - api body
 * @param {*} callBack - to sync redux state
 * @returns {string} - owner id
 */
export const saveForm = async (data, callBack) => {
  const response = await merchantFetch({
    url: 'merchant/international/apm_request',
    method: 'post',
    data,
  });
  if (response.success) {
    callBack(response.data);
    return response.data?.[OWNER_DETAILS]?.[0]?.id;
  }
  return false;
};

//new object is returned after save is successfull
export const saveDocument = async (file, progressTracker, key) => {
  const formData = new FormData();
  formData.append('purpose', 'international_enablement');
  formData.append('file', file);
  const response = await merchantFetch({
    url: 'documents',
    method: 'post',
    data: formData,
    onUploadProgress: progressTracker,
  });
  if (response.data) {
    const { id, display_name } = response.data;
    return {
      id,
      display_name,
      key,
    };
  }
  return false;
};

/**
 *
 * @param {*} owner_id - id of owner to delete
 */
export const deleteOwner = async (owner_id) => {
  await merchantFetch({
    url: 'merchant/international/apm_request/owner',
    method: 'delete',
    data: { owner_id },
  });
};
