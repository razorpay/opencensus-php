/* eslint-disable import/no-restricted-paths */
import React, { useState } from 'react';
import { closeModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import ModalHeader from 'common/ui/ModalHeader';
import { Box, ModalFooter, Alert } from '@razorpay/blade/components';
import { Button, MultiSelectDropdown } from 'merchant_common/views/Reports/components';
import { useStore } from 'shell/commonStore';
import { fetchBouncememo, fetchBounceMemoBulk } from './BounceMemo.types';
import pdfCreation from 'merchant/views/Transactions/v1/Payments/components/PdfCreation';
import { PaymentStatus } from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/types';
import {
  PAYMENT_METHOD_OPTIONS,
  ALL_OPTION,
  DATE_RANGE_PRESETS,
  MIN_START_DATE,
  TITLE,
} from './constants';
import ShowWhen from 'merchant/components/ShowWhen';
import { getOptionsAccordingToAllOptionSelected } from 'merchant_common/views/Reports/components/ReportModal/components/DownloadReport/Utils/batchPaymentPages';
import DateRangePicker from 'common/ui/DateRangePicker';
import moment from 'moment';
import exportCSV from './Csvgenerate';

//Bounce memo Changes and added bulk bounce memo
//When the merchant clicks the download button then fetch the data from emandate service and pass the data into the pdfcreation code to generate the bounce memo pdf.

export interface SelectedRangeType {
  startDate: moment.Moment;
  endDate: moment.Moment;
}

export interface User {
  id: string;
}

export interface BounceMemoPopupProps {
  Paymentpage: string; // Add union types or more specific strings if applicable (e.g., 'SinglePage' | 'BulkPage')
  paymentID: string;
  user: User;
}

const ModalFooterButtons = ({
  testIDCancel,
  testIDDownload,
  isLoading,
  onClickDownload,
  closeModal,
}) => (
  <ModalFooter>
    <Box display="flex" justifyContent="flex-end">
      <Button
        testID={testIDCancel}
        type="button"
        variant="tertiary"
        marginX="spacing.5"
        isDisabled={isLoading}
        onClick={closeModal}
      >
        Cancel
      </Button>
      <Button
        testID={testIDDownload}
        type="button"
        variant="primary"
        isLoading={isLoading}
        onClick={onClickDownload}
      >
        Download
      </Button>
    </Box>
  </ModalFooter>
);

const BounceMemoPopup: React.FC<BounceMemoPopupProps> = ({ paymentPage, paymentID, user }: any) => {
  //Changes for bulk bounce memo
  const showNotification = useStore((state) => state.showNotification);
  const [isSubmitButtonLoading, setSubmitButtonLoading] = useState(false);
  const [selectedPaymentMethod, setselectedPaymentMethod] = useState<PaymentStatus[]>([]);
  const isOutsideRange = (day) => day.isAfter(moment()) || day.isBefore(moment(MIN_START_DATE));
  const [date, setDate] = useState({ from: '', to: '' });
  const onDatesChange = (from, to) => {
    setDate({
      from: from.unix(),
      to: to.unix(),
    });
  };

  const hasSelectedMethods = selectedPaymentMethod?.length > 0;
  const isAllOptionSelected = selectedPaymentMethod?.[0]?.value === ALL_OPTION.value;

  const paymentMethods =
    hasSelectedMethods && !isAllOptionSelected
      ? selectedPaymentMethod.map((method) => method.value) // Extract values from selected methods
      : undefined;

  const fromDate = moment.unix(Number(date.from)).format('DD/MM/YYYY');
  const toDate = moment.unix(Number(date.to)).format('DD/MM/YYYY');

  const onBulkDownload = async () => {
    setSubmitButtonLoading(true);
    const DateParams = {
      ...date,
      paymentMethods,
      paymentID,
    };
    try {
      const response = await fetchBounceMemoBulk(DateParams);
      if (!response.data || !response.data.data) {
        showNotification({
          type: 'error',
          message: response.data.error.description,
        });
        throw new Error();
      } else if (response.data.data.length === 0) {
        showNotification({
          type: 'error',
          message: `No bounce memo available for the specified date range (${fromDate}  to ${toDate})`,
        });
        return; // Exit the function early since there's no data to export
      }
      const url = window.location.href;
      exportCSV(response.data.data, url);
    } catch (err) {
      showNotification({
        type: 'error',
        message: 'Unable to fetch Bounce memo information at this moment, please try again later.',
      });
    } finally {
      setSubmitButtonLoading(false);
    }
  };

  const handlePaymentStatusChange = (selectedValues: PaymentStatus[]): void => {
    const values = getOptionsAccordingToAllOptionSelected<PaymentStatus[]>({
      currSelectedOptions: selectedValues,
      prevSelectedOptions: selectedPaymentMethod,
    });

    setselectedPaymentMethod(values);
  };

  //End of changes for bulk bounce memo

  const { id: merchantId } = user;

  const params = {
    paymentID,
    merchantId,
  };

  const fetchBounceMemoSingle = async () => {
    setSubmitButtonLoading(true);
    try {
      const response = await fetchBouncememo(params);
      if (!response.data || !response.data.data) {
        showNotification({
          type: 'error',
          message: response.data.error,
        });
      }
      pdfCreation(response.data.data, merchantId, 'singlePage');
    } catch (err) {
      showNotification({
        type: 'error',
        message: 'Unable to fetch Bounce memo information at this moment, please try again later.',
      });
    } finally {
      setSubmitButtonLoading(false);
    }
  };

  return (
    <div>
      <div className="partner-submerchant-modal">
        <ModalHeader title="Download Failed Transaction Memo" onCloseClick={closeModal} />
        <div className="modal-body">
          <Alert
            testID="bounce-memo-modal-alert"
            marginTop="spacing.4"
            isDismissible={false}
            description={TITLE}
            isFullWidth={false}
            color="negative"
          />
          <ShowWhen additionalCondition={() => paymentPage === 'failedPaymentPage'}>
            <MultiSelectDropdown
              label="Payment Method"
              value={selectedPaymentMethod}
              onChange={handlePaymentStatusChange}
              shouldCloseDropdownOnSelect={true}
              options={PAYMENT_METHOD_OPTIONS}
              labelKey="label"
              ariaLabelBy="Payment Method"
            />

            <div style={{ padding: '1px' }}>
              <div className="form-group datepicker-group">
                <label>Duration</label>
                <DateRangePicker
                  presets={DATE_RANGE_PRESETS}
                  onDatesChange={onDatesChange}
                  isOutsideRange={isOutsideRange}
                  minStartDate={moment(MIN_START_DATE)}
                />
              </div>
            </div>
          </ShowWhen>
        </div>
        <ShowWhen additionalCondition={() => paymentPage === 'singlePage'}>
          <ModalFooterButtons
            testIDCancel="bounce-memo-modal-cancel"
            testIDDownload="bounce-memo-download-btn"
            isLoading={isSubmitButtonLoading}
            onClickDownload={fetchBounceMemoSingle}
            closeModal={closeModal}
          />
        </ShowWhen>
        <ShowWhen additionalCondition={() => paymentPage === 'failedPaymentPage'}>
          <ModalFooterButtons
            testIDCancel="bounce-memo-modal-cancel-bulk"
            testIDDownload="bounce-memo-download-btn-bulk"
            isLoading={isSubmitButtonLoading}
            onClickDownload={onBulkDownload}
            closeModal={closeModal}
          />
        </ShowWhen>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(BounceMemoPopup);
