import { createContext, useState, useCallback } from 'react';
import { LOADING } from 'merchant/components/Activation/Constants';
import { scrollToTop } from 'common/utils/rzp-utils';
import { formInitialValues } from './constants';

export const formContext = createContext();

const FormProvider = ({ children }) => {
  const [selectedTab, setSelectedTab] = useState(0); //to track current tab in form
  const [isLoading, setLoading] = useState(LOADING.INITIAL); //to handle form saving
  const [initialValues, setInitialValues] = useState(formInitialValues); //so set forms initialValues
  const [isPurposecodeSpecial, setIsPurposecodeSpecial] = useState(false); //to check if IEC input needs to be shown
  const [ownerCount, setOwnerCount] = useState(0);
  const [activeOwner, setActiveOwner] = useState(0); //current owner in ownership form
  const [documents, setDocuments] = useState({}); //to track uploaded documents
  const [isUneditable, setIsUneditable] = useState({}); //to check if an input is editable or not
  const [purposeCode, setPurposeCode] = useState([]); //purpose code list from api

  const onTabClick = useCallback(
    (index, ref) => {
      setSelectedTab(index);
      scrollToTop(ref);
    },
    [selectedTab],
  );

  /**
   * @param  {} tag unique key to refer to a particular input in form
   * @param  {} file response from upload document api
   */
  const updateDocuments = useCallback(
    (tag, file) => {
      setDocuments((prevState) => {
        const updatedDocuments = { ...prevState };
        updatedDocuments[tag] = file;
        return updatedDocuments;
      });
    },
    [documents],
  );

  const value = {
    selectedTab,
    onTabClick,
    initialValues,
    setInitialValues,
    isLoading,
    setLoading,
    isPurposecodeSpecial,
    setIsPurposecodeSpecial,
    activeOwner,
    setActiveOwner,
    documents,
    updateDocuments,
    setDocuments,
    isUneditable,
    setIsUneditable,
    purposeCode,
    setPurposeCode,
    ownerCount,
    setOwnerCount,
  };

  return <formContext.Provider value={value}>{children}</formContext.Provider>;
};

export default FormProvider;
