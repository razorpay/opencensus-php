import { renderHook, act } from '@testing-library/react-hooks';
import * as Formik from 'formik';

import * as Ajax from 'merchant/utils/ajax';
import {
  ADDITIONAL_DOCUMENTS_FOR_BUSINESS_TYPE,
  FORM_INITIAL_VALUES,
} from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/constants';
import { useAdditionalDocuments } from 'merchant/views/Settings/PaymentMethods/components/MethodEnablementForm/hooks/useAdditionalDocuments';
import {
  PROPRIETORSHIP,
  PUBLIC,
  PRIVATE,
  NGO,
} from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';
import { waitFor } from 'test-utils';

const useFormikContextSpy = jest.spyOn(Formik, 'useFormikContext');
const merchantFetchSpyOn = jest.spyOn(Ajax, 'merchantFetch');
const user = {
  business_type: '1',
};

const saveFormData = jest.fn();
const showNotification = jest.fn();
const TEST_FILE = new File([''], 'document1');

describe('Tests for useAdditionalDocuments hook - MethodEnablementForm', () => {
  beforeEach(() => {
    useFormikContextSpy.mockReturnValue({
      errors: {},
      values: FORM_INITIAL_VALUES,
      setFieldValue: jest.fn(),
    });
  });

  describe('Test for handleSelect callback  - useAdditionalDocuments hook', () => {
    test('should initialize with empty selectedDocument and selectedOption', () => {
      const { result } = renderHook(() => useAdditionalDocuments({ user }));

      expect(result.current.selectedDocument).toEqual([]);
      expect(result.current.selectedOption).toEqual([]);
    });

    test('should update selectedOption when handleSelect is called', () => {
      const { result } = renderHook(() => useAdditionalDocuments({ user }));
      const value = 'option1';
      const index = 0;

      act(() => {
        result.current.handleSelect(value, index);
      });

      expect(result.current.selectedOption[index]).toEqual(value);
    });
  });

  describe('Test for bussiness type and document mapping - useAdditionalDocuments hook', () => {
    test.each([PROPRIETORSHIP, PUBLIC, PRIVATE, NGO])(
      'should return docs for business type',
      (business_type) => {
        const { result } = renderHook(() =>
          useAdditionalDocuments({
            user: {
              business_type,
            },
          }),
        );
        expect(result.current.docs).toEqual(ADDITIONAL_DOCUMENTS_FOR_BUSINESS_TYPE[business_type]);
      },
    );
  });

  describe('Tests for filterOtherSelectOptions callback  - useAdditionalDocuments hook', () => {
    test('should return filtered options', () => {
      const { result } = renderHook(() => useAdditionalDocuments({ user }));

      expect(
        result.current.filterOtherSelectOptions(0, [{ name: 'option1' }, { name: 'option2' }]),
      ).toEqual([{ name: 'option1' }, { name: 'option2' }]);
    });
    test('should return only one option if only one option is selected', () => {
      const { result } = renderHook(() => useAdditionalDocuments({ user }));

      act(() => {
        result.current.handleSelect('option1', 0);
      });

      expect(
        result.current.filterOtherSelectOptions(0, [{ name: 'option1' }, { name: 'option2' }]),
      ).toEqual([{ name: 'option1' }, { name: 'option2' }]);
    });
    test('should return only one option if both the options are selected for 0 index', () => {
      const { result } = renderHook(() => useAdditionalDocuments({ user }));

      act(() => {
        result.current.handleSelect('option1', 0);
        result.current.handleSelect('option2', 1);
      });

      expect(
        result.current.filterOtherSelectOptions(0, [{ name: 'option1' }, { name: 'option2' }]),
      ).toEqual([{ name: 'option1' }]);
    });
    test('should return only one option if both the options are selected for 1 index', () => {
      const { result } = renderHook(() => useAdditionalDocuments({ user }));

      act(() => {
        result.current.handleSelect('option1', 0);
        result.current.handleSelect('option2', 1);
      });

      expect(
        result.current.filterOtherSelectOptions(1, [
          { name: 'option1' },
          { name: 'option2' },
          { name: 'option3' },
        ]),
      ).toEqual([{ name: 'option2' }, { name: 'option3' }]);
    });
  });

  describe('Tests for handleUpload callback - useAdditionalDocuments hook', () => {
    test('should upload the document and update the form', async () => {
      const { result } = renderHook(() =>
        useAdditionalDocuments({ user, showNotification, saveFormData }),
      );

      merchantFetchSpyOn.mockReturnValue(
        Promise.resolve({ data: { id: 'document1', display_name: 'Document.png' } }),
      );

      const onUploadProgress = jest.fn();

      const formData = new FormData();
      formData.append('purpose', 'international_enablement');
      formData.append('file', TEST_FILE);

      act(() => {
        result.current.handleUploadFile('document1', TEST_FILE, onUploadProgress);
      });

      expect(merchantFetchSpyOn).toHaveBeenCalledWith(
        expect.objectContaining({
          data: formData,
          method: 'post',
          url: 'documents',
          onUploadProgress,
        }),
      );

      await waitFor(() =>
        expect(saveFormData).toHaveBeenCalledWith(
          expect.objectContaining({
            values: {
              documents: {
                document1: [
                  {
                    display_name: 'Document.png',
                    id: 'document1',
                  },
                ],
              },
              kyc_tnc_accepted: false,
              signatory: null,
              vkyc_tnc_accepted: false,
            },
          }),
        ),
      );
    });

    test('should show notification on error', async () => {
      const { result } = renderHook(() =>
        useAdditionalDocuments({ user, showNotification, saveFormData }),
      );

      merchantFetchSpyOn.mockReturnValue(Promise.reject({ errors: ['An error'] }));

      act(() => {
        result.current.handleUploadFile('document1', TEST_FILE);
      });

      await waitFor(() =>
        expect(showNotification).toHaveBeenCalledWith(
          expect.objectContaining({
            message: ['An error'],
            type: 'error',
          }),
        ),
      );
    });

    test('should not call saveFormData function if data is empty', async () => {
      const { result } = renderHook(() =>
        useAdditionalDocuments({ user, showNotification, saveFormData }),
      );

      merchantFetchSpyOn.mockReturnValue(Promise.resolve({ data: null }));

      act(() => {
        result.current.handleUploadFile('document1', TEST_FILE);
      });

      await waitFor(() => expect(saveFormData).not.toHaveBeenCalled());
    });

    test('should remove selectedDocument when handleSelect is called with null value', () => {
      const { result } = renderHook(() =>
        useAdditionalDocuments({ user, showNotification, saveFormData }),
      );
      const index = 0;
      const value = 'msme_certificate';

      act(() => {
        result.current.handleSelect(value, index);
      });

      expect(result.current.selectedDocument[index]).toEqual(
        ADDITIONAL_DOCUMENTS_FOR_BUSINESS_TYPE[PROPRIETORSHIP][index].options.find(
          (item) => item.name === value,
        ),
      );

      act(() => {
        result.current.handleSelect(null, index);
      });

      expect(result.current.selectedDocument[index]).toBeUndefined();
    });
  });

  describe('Test for handleFileRemove callback - useAdditionalDocuments hook', () => {
    test('should remove the document from the form', () => {
      const { result } = renderHook(() =>
        useAdditionalDocuments({ user, showNotification, saveFormData }),
      );

      act(() => {
        result.current.handleFileRemove('document1', 'document1');
      });

      expect(saveFormData).toHaveBeenCalledWith(
        expect.objectContaining({
          values: {
            documents: {
              document1: null,
            },
            kyc_tnc_accepted: false,
            signatory: null,
            vkyc_tnc_accepted: false,
          },
        }),
      );
    });
  });
});
