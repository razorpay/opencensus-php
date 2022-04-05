import { useCallback, useEffect, useState, useMemo } from 'react';
import { connect } from 'react-redux';
import { closeModal } from 'merchant_common/reducers/modals';
import { fetchFircFiles } from './service';
import { MONTHS, getDataFromAPI, getListOfYears, organiseFiles } from './utility';
import moment from 'moment';
import { Label } from 'common/new-ui/Input/index';
import Form from 'common/new-ui/Form';
import Spinner from 'common/ui/Spinner';
import ModalHeader from 'common/ui/ModalHeader';
import FIRCInfo from './FIRCInfo';
import FircFiles from './FircFiles';

const START_YEAR = 2021;
const YEARS = getListOfYears(START_YEAR);

const DownloadFIRCForm = ({ closeModal }) => {
  const [year, setYear] = useState(moment().format('YYYY'));
  const [month, setMonth] = useState(moment().subtract(1, 'months').format('MM'));
  const [files, setFiles] = useState({ data: [], isLoading: true });

  const isInvalidDate = useMemo(
    () => Number(year) < START_YEAR || (Number(year) === START_YEAR && Number(month) < 7),
    [year, month],
  );

  const getFircFiles = useCallback(async () => {
    try {
      const response = await fetchFircFiles(month, year);
      const data = getDataFromAPI(response);
      setFiles({ data: organiseFiles(data), isLoading: false });
    } catch (error) {
      setFiles({ data: [], isLoading: false });
    }
  }, [month, year]);

  const checkIfInvalidDate = useCallback(() => {
    setFiles({ data: [], isLoading: !isInvalidDate });
    if (!isInvalidDate) {
      getFircFiles();
    }
  }, [month, year]);

  const handleYear = useCallback((event) => {
    const selectedYear = event.target.value;
    setYear(selectedYear);
  }, []);

  const handleMonth = useCallback((event) => {
    const selectedMonth = event.target.value;
    setMonth(selectedMonth);
  }, []);

  const Dropdown = useCallback(
    ({ label, value, list, handleChange, classList }) => (
      <div>
        <Label text={label} className="label" />
        <select className={`select ${classList}`} value={value} onChange={handleChange}>
          {list.map((item) => (
            <option value={item.value} key={item.value}>
              {item.label}
            </option>
          ))}
        </select>
      </div>
    ),
    [],
  );

  useEffect(() => {
    checkIfInvalidDate();
  }, [year, month]);

  return (
    <Form>
      <ModalHeader title="Download FIRC" onCloseClick={closeModal} />
      <div className="firc-form-container">
        <div className="dropdown-container">
          <Dropdown label="Year" list={YEARS} value={year} handleChange={handleYear} />
          <Dropdown
            label="Month"
            list={MONTHS}
            value={month}
            handleChange={handleMonth}
            classList={isInvalidDate && 'select--invalid'}
          />
        </div>

        {files.isLoading && (
          <div className="spinner-container">
            <Spinner />
          </div>
        )}

        {files.data.length === 0 && !files.isLoading && (
          <FIRCInfo closeModal={closeModal} isInvalidDate={isInvalidDate} />
        )}

        {files.data.length !== 0 && !files.isLoading && (
          <FircFiles files={files.data} month={month} year={year} />
        )}
      </div>
    </Form>
  );
};

export default connect(null, { closeModal })(DownloadFIRCForm);
