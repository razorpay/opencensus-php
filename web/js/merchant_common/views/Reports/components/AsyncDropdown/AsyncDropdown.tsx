import React, {
  Dispatch,
  SetStateAction,
  createContext,
  useCallback,
  useRef,
  useState,
} from 'react';
import debounce from 'common/utils/debounce';
import { Dropdown } from 'merchant_common/views/Reports/components';
import { AsyncDropdownPropsType } from './types';
import { useClickOutSide } from 'merchant_common/views/Reports/hooks';

export const AsyncDropdownContext = createContext({
  isAsyncDropdown: false,
  isAsyncLoading: true,
  setOptionsCallback: () => {},
  setAsyncLoading: () => {},
} as {
  isAsyncDropdown: boolean;
  isAsyncLoading: boolean;
  setOptionsCallback;
  setAsyncLoading: Dispatch<SetStateAction<boolean>>;
});

export const AsyncDropdown = <ItemType, AllowMultiple, Virtualized>({
  promise,
  parseData,
  debounceInterval = 500,
  onError = () => {},
  onSuccess = () => {},
  ...otherProps
}: AsyncDropdownPropsType<ItemType, AllowMultiple, Virtualized> & {
  children?: JSX.Element;
}): JSX.Element => {
  const [options, setOptions] = useState<ItemType[]>([]);
  const [isAsyncLoading, setAsyncLoading] = useState(false);
  const ref = useRef(null);
  const abortController = useRef(new AbortController());
  const setOptionsCallback = useCallback(setOptions, []);

  const debouncedOnSearch: (searchedFor: string) => void = useCallback(
    debounce((query) => {
      if (query.length) {
        abortController.current.abort();
        abortController.current = new AbortController();
        promise({ query, signal: abortController.current.signal })
          .then((data) => {
            if (query.length) {
              const parsedData = parseData(data);
              const parsedOptions =
                Boolean(parsedData) && Array.isArray(parsedData) ? parsedData : [];
              setOptionsCallback(parsedOptions);
            } else {
              setOptionsCallback([]);
            }
            onSuccess(data);
          })
          .catch((err) => {
            onError(err);
          })
          .finally(() => {
            setAsyncLoading(false);
          });
      }
    }, debounceInterval),
    [],
  );

  useClickOutSide([ref], () => {
    abortController.current.abort();
  });

  const renderDropdown = (props) => {
    return <Dropdown {...props} />;
  };

  const combinedDropdownProps = {
    ...otherProps,
    options,
    onSearchInput: debouncedOnSearch,
    isSearchable: true,
  };

  return (
    <div ref={ref}>
      <AsyncDropdownContext.Provider
        value={{
          isAsyncDropdown: true,
          isAsyncLoading,
          setOptionsCallback,
          setAsyncLoading,
        }}
      >
        {renderDropdown(combinedDropdownProps)}
      </AsyncDropdownContext.Provider>
    </div>
  );
};
