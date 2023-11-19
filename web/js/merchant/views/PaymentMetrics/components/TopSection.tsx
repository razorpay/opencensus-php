import moment from 'moment';
import React, { useEffect, useState } from 'react';
import { getIndustryOverallCRData, getOverallCRData } from 'merchant/views/PaymentMetrics/helpers';
import CRComparison from './CRComparison';
import IndustryCRComparison from './IndustryCrComparison';
import { CardContainer } from './styled';

const lte = moment().endOf('day').unix(); // today
const gte = moment().subtract(7, 'days').startOf('day').unix(); // lwsd

const getTimeStampValue = (
  data: Array<{ timestamp: number; value: number }>,
  timestamp: number,
) => {
  const value = data.filter((item) => item.timestamp === timestamp)?.[0]?.value || 0;
  return Math.round(value);
};

const TopSection = ({ category = '' }): React.ReactElement => {
  const [isFetchingCR, setFetchingCr] = useState(false);
  const [errorCr, setErrorCr] = useState('');
  const [crData, setCrData] = useState({ yesterday: 0, today: 0, lwsd: 0 });

  const [isFetchingIndustryCr, setFetchingIndustryCr] = useState(false);
  const [errorIndustryCr, setErrorIndustryCr] = useState('');
  const [crIndustryData, setIndustryCrData] = useState({ yesterday: 0, today: 0, lwsd: 0 });

  useEffect(() => {
    setFetchingCr(true);
    setFetchingIndustryCr(true);
    getOverallCRData({ lte, gte })
      .then((resp) => {
        if (resp.data?.ERROR || resp.errors) {
          setErrorCr(resp.data?.ERROR || resp.errors?.[0]);
        } else {
          const yesterday = moment().subtract(1, 'days').startOf('day').unix();
          const today = moment().startOf('day').unix();
          const data =
            resp.data?.checkout_overall_cr?.result ||
            ([] as Array<{ timestamp: number; value: number }>);
          setCrData({
            yesterday: getTimeStampValue(data, yesterday),
            today: getTimeStampValue(data, today),
            lwsd: getTimeStampValue(data, gte),
          });
        }
      })
      .catch(
        /* istanbul ignore next */
        () => {
          setErrorCr('Try Again Later!');
        },
      )
      .finally(() => {
        setFetchingCr(false);
      });

    if (category) {
      getIndustryOverallCRData({ lte, gte, category })
        .then((resp) => {
          if (resp.data?.ERROR || resp.errors) {
            setErrorIndustryCr('Try Again Later!');
          } else {
            const yesterday = moment().subtract(1, 'days').startOf('day').unix();
            const today = moment().startOf('day').unix();
            const data =
              resp.data?.checkout_industry_level_overall_cr?.result ||
              ([] as Array<{ timestamp: number; value: number }>);
            setIndustryCrData({
              yesterday: getTimeStampValue(data, yesterday),
              today: getTimeStampValue(data, today),
              lwsd: getTimeStampValue(data, gte),
            });
          }
        })
        .catch(
          /* istanbul ignore next */
          () => {
            setErrorIndustryCr('Try Again Later!');
          },
        )
        .finally(() => {
          setFetchingIndustryCr(false);
        });
    }
  }, []);
  return (
    <CardContainer>
      <CRComparison
        crData={crData}
        industryData={crIndustryData}
        isFetching={isFetchingCR}
        error={errorCr}
      />
      {category && (
        <IndustryCRComparison
          crData={crIndustryData}
          isFetching={isFetchingIndustryCr}
          error={errorIndustryCr}
        />
      )}
    </CardContainer>
  );
};

export default TopSection;
