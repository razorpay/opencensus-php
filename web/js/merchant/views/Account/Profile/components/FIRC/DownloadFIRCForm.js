import { useCallback, useEffect, useState } from 'react';
import { connect } from 'react-redux';
import moment from 'moment';

import Button from 'common/new-ui/Button';
import Form from 'common/new-ui/Form';
import Spinner from 'common/ui/Spinner';
import ModalHeader from 'common/ui/ModalHeader';
import { Label } from 'common/new-ui/Input/index';
import { closeModal } from 'merchant_common/reducers/modals';
import { classList } from 'common/utils/rzp-utils';
import FIRCInfo from './FIRCInfo';
import FircFiles from './FircFiles';
import { fetchFircFiles, downloadFiles } from './service';
import { MONTHS, getDataFromAPI, getListOfYears } from './utility';

const START_YEAR = 2021;
const LIST_OF_YEARS = getListOfYears(START_YEAR);

const DownloadFIRCForm = (props) => {
  const [year, setYear] = useState(moment().format('YYYY'));
  const [month, setMonth] = useState(moment().subtract(1, 'months').format('MM'));
  const [showSupportInfo, setShowSupportInfo] = useState(false);
  const [files, setFiles] = useState([]);
  const [loading, setLoading] = useState(true);
  const [isInvalidDate, setIsInvalidDate] = useState(false);

  const getFircFiles = useCallback(() => {
    fetchFircFiles(month, year)
      .then((response) => {
        const data = getDataFromAPI(response);
        setFiles(data);
        setShowSupportInfo(data.length === 0);
        setLoading(false);
      })
      .catch((err) => {
        setLoading(false);
        setShowSupportInfo(true);
        throw new Error(err);
      });
  }, [month, year]);

  const checkWhetherInvalidDate = useCallback(() => {
    // Check whether date is before June 2021 - In that case, show text asking to raise ticket
    const isInvalid =
      Number(year) < START_YEAR || (Number(year) === START_YEAR && Number(month) < 7);
    setFiles([]);
    if (isInvalid) {
      setIsInvalidDate(true);
      setShowSupportInfo(true);
      setLoading(false);
    } else {
      setIsInvalidDate(false);
      setShowSupportInfo(false);
      setLoading(true);
      getFircFiles();
    }
  }, [month, year, getFircFiles]);

  const handleYear = useCallback(
    (event) => {
      const selectedYear = event.target.value;
      setYear(selectedYear);
    },
    [setYear],
  );

  const handleMonth = useCallback(
    (event) => {
      const selectedMonth = event.target.value;
      setMonth(selectedMonth);
    },
    [setMonth],
  );

  useEffect(() => {
    checkWhetherInvalidDate();
  }, [checkWhetherInvalidDate]);

  const { closeModal: closeModalProp } = props;

  return (
    <Form>
      <ModalHeader
        title="Download FIRC"
        extraClass="purpose-code-modal-heading"
        onCloseClick={closeModalProp}
      />
      <div className="firc-form-container">
        <div className="dropdown-container">
          <div>
            <Label text="Year" className="label" />
            <select className="select" value={year} onChange={handleYear}>
              {LIST_OF_YEARS.map((yr) => (
                <option value={yr} key={yr}>
                  {yr}
                </option>
              ))}
            </select>
          </div>
          <div>
            <Label text="Month" className="label" />
            <select
              className={classList('select', showSupportInfo && isInvalidDate && 'select--invalid')}
              value={month}
              onChange={handleMonth}
            >
              {MONTHS.map((m) => (
                <option key={m.value} value={m.value}>
                  {m.label}
                </option>
              ))}
            </select>
          </div>
        </div>

        {loading && (
          <div class="spinner-container">
            <Spinner />
          </div>
        )}

        {/* Support flow info for docs before June 2021 */}
        {showSupportInfo && (
          <FIRCInfo
            closeModal={closeModalProp}
            filesNotFound={files.length > 0}
            loading={loading}
            isInvalidDate={isInvalidDate}
          />
        )}

        {!showSupportInfo && !loading && <FircFiles files={files} month={month} year={year} />}

        <Button.Primary
          className="btn btn-primary btn-block"
          disabled={files.length === 0}
          onClick={() => downloadFiles({ month, year })}
        >
          {files.length > 1 ? 'Download All' : 'Download'}
        </Button.Primary>
      </div>
    </Form>
  );
};

export default connect(null, { closeModal })(DownloadFIRCForm);
