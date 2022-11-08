import { useCallback, useEffect, useState } from 'react';

// apis
import { merchantFetch } from 'merchant/utils/ajax';

// types
type HSCodeListType = {
  description: string;
  label: string;
  value: string;
};

type HSCodeState = {
  isLoading: boolean;
  data: { label: string; codes: HSCodeListType[] }[];
};

type HSCodeResponse = { hsGroup: string; codes: { description: string; hsCode: string }[] }[];

type UseAllHSCodeType = {
  hsCode?: string;
  close?: () => void;
  showNotification?: (arg: { type: 'error' | 'success'; message: string }) => void;
  getHSCodeDetails?: () => void;
};

type UseAllHSCodeReturnType = {
  isLoading: HSCodeState['isLoading'];
  data: HSCodeState['data'];
  hasConfirm: boolean;
  selectedCode: HSCodeListType | null;
  onSave: () => void;
  onBack: () => void;
  onConfirm: () => void;
  onSelect: (code: HSCodeListType | null) => void;
};

type UseHSCodeSearchReturnType = {
  search: string;
  items: HSCodeListType[];
  onClear: () => void;
  onSearch: (query: string) => void;
};

const fetchAllHSCodes = () => {
  return merchantFetch({
    url: 'merchant/hscode',
    method: 'get',
  });
};

const updateMerchantHSCode = (data) => {
  return merchantFetch({
    url: 'merchant/hscode',
    method: 'patch',
    data,
  });
};

const formatHSCodeList = (data: HSCodeResponse): HSCodeState['data'] => {
  return Array.isArray(data)
    ? data.map((item) => {
        return {
          label: item.hsGroup,
          codes: item.codes.map((code) => ({
            label: code.hsCode,
            value: code.hsCode,
            description: code.description,
          })),
        };
      })
    : [];
};

const findHSCode = (list: HSCodeState['data'], hsCode?: string) => {
  let selectedHSCode: HSCodeListType | null = null;
  if (!hsCode) {
    return selectedHSCode;
  }

  list.forEach((item) => {
    item.codes.forEach((code) => {
      if (code.value === hsCode) {
        selectedHSCode = code;
      }
    });
  });

  return selectedHSCode;
};

/**
 * Custom hook to use HSCode APIs
 * @param {UseAllHSCodeType} args parameter types
 * @returns {UseAllHSCodeReturnType} Returns states and actions
 */
export const useAllHSCode = ({
  hsCode,
  close,
  showNotification,
  getHSCodeDetails,
}: UseAllHSCodeType): UseAllHSCodeReturnType => {
  const [list, setList] = useState<HSCodeState>({
    isLoading: false,
    data: [],
  });
  const [hasConfirm, setConfirm] = useState(false);
  const [selectedCode, setSelectedCode] = useState<HSCodeListType | null>(() => {
    if (hsCode) {
      return { value: hsCode, description: hsCode, label: hsCode };
    }
    return null;
  });

  const notify: UseAllHSCodeType['showNotification'] = useCallback(
    (arg) => {
      if (typeof showNotification === 'function') {
        showNotification(arg);
      }
    },
    [showNotification],
  );

  const getList = useCallback(async () => {
    try {
      setList({ isLoading: true, data: [] });

      const { data } = await fetchAllHSCodes();
      const list = formatHSCodeList(data);
      const selectedHSCode = findHSCode(list, hsCode);

      setList({ isLoading: false, data: formatHSCodeList(data) });
      setSelectedCode(selectedHSCode);
    } catch (err) {
      const errors = err && typeof err === 'object' ? (err as { errors: string[] }).errors : [];
      const message =
        Array.isArray(errors) && errors.length ? errors[0] : 'Fetching purpose code list failed.';
      notify({
        type: 'error',
        message,
      });
      setList({ isLoading: false, data: [] });
    }
  }, [hsCode, notify]);

  const onConfirm = () => setConfirm(true);

  const onBack = () => setConfirm(false);

  const onSelect = (code: HSCodeListType | null) => setSelectedCode(code);

  const onSave = useCallback(async () => {
    try {
      if (selectedCode) {
        await updateMerchantHSCode({
          hs_code: selectedCode.value,
        });
        if (typeof getHSCodeDetails === 'function') {
          getHSCodeDetails();
        }
        notify({
          type: 'success',
          message: 'HS code updated successfully',
        });
      }
    } catch (err) {
      notify({
        type: 'error',
        message: 'Failed to update HS code. Please try again later.',
      });
    } finally {
      if (typeof close === 'function') {
        close();
      }
    }
  }, [close, getHSCodeDetails, notify, selectedCode]);

  useEffect(() => {
    getList();
  }, [getList]);

  return {
    isLoading: list.isLoading,
    data: list.data,
    hasConfirm,
    selectedCode,
    onSave,
    onBack,
    onSelect,
    onConfirm,
  };
};

/**
 * Custom hook to search through HSCode list
 * @param {HSCodeState} list type
 * @returns {UseHSCodeSearchReturnType} returns states and actions
 */
export const useHSCodeSearch = (list: HSCodeState['data']): UseHSCodeSearchReturnType => {
  const [items, setItems] = useState<HSCodeListType[]>([]);
  const [search, setSearch] = useState('');

  const onSearch = useCallback(
    (query: string) => {
      const filteredItems: HSCodeListType[] = [];
      if (query && query.trim() !== '' && list.length) {
        const queryLower = query.toLowerCase();

        list.forEach((item) => {
          item.codes.forEach((code) => {
            const isValueMatch = code.value.toLowerCase().includes(queryLower);
            const isDescriptionMatch = code.description.toLowerCase().includes(queryLower);
            if (isValueMatch || isDescriptionMatch) {
              filteredItems.push(code);
            }
          });
        });
      }
      setSearch(query);
      setItems(filteredItems);
    },
    [list],
  );

  const onClear = useCallback(() => {
    setSearch('');
    setItems([]);
  }, []);

  return {
    search,
    items,
    onClear,
    onSearch,
  };
};
