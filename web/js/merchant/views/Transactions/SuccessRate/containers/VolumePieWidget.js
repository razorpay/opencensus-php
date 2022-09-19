import React from 'react';
import { connect } from 'react-redux';
import { Pie } from 'react-chartjs-2';
import compact from 'lodash/compact';
import isEmpty from 'lodash/isEmpty';
import { getUser } from 'merchant/store';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import Spinner from 'common/ui/Spinner';
import StyledHeader from '../components/StyledHeader';
import NoDataMessage from '../components/NoDataMessage';
import { pieChartOptions as options, piePlugins as plugins } from '../chartConfig';
import { getPieChartData, getTagLabel, getFormattedNumber } from '../helper';
import { TAG_OVERALL_MAP } from '../constants';

const renderInfoCard = ({ name, successful, total, sr } = {}, index) => {
  if (name === 'others' && !sr) return null;
  const label = getTagLabel(name);
  return (
    <div key={`${name}___${index}`} className="col-sm-6 info-card">
      <StyledHeader text={label} />
      <div className="info-card__label">
        <p className="label-text">Successful / Total attempts</p>
      </div>
      <div className="info-card__value">
        <p>
          <span className="highlight">{getFormattedNumber(successful)}</span>/
          {getFormattedNumber(total)} ({sr}%)
        </p>
      </div>
    </div>
  );
};

const VolumePieWidget = (props) => {
  const { isLoading, activeTab, tab = {} } = props;
  const { group_by = '', data } = tab;
  const user = getUser();
  const _group_by = activeTab === 'Overall' || !user.isOptimizerEnabled ? group_by : 'procurer';
  const groupData = data?.groups?.[_group_by] ?? [];
  const pieChartData = getPieChartData(groupData);
  const compactData = compact(pieChartData?.datasets?.[0]?.data);

  if (isLoading) {
    return (
      <div className="box-widget volume-container">
        <StyledHeader text="Volume of attempts" />
        <Spinner />
      </div>
    );
  }

  return (
    <div className="box-widget volume-container">
      <div className="row">
        <div className="col-sm-12 col-md-5">
          <div className="chart-col">
            <StyledHeader text="Overall payment attempts" />
            {!isEmpty(compactData) && (
              <div className="pie-chart">
                <Pie data={pieChartData} options={options} plugins={plugins} />
              </div>
            )}
          </div>
        </div>
        <div className="col-sm-12 col-md-7">
          {!isLoading && Boolean(data?.sr) && (
            <div className="col-sm-6 info-card">
              {isLoading ? (
                <PlaceholderLoader style={{ marginBottom: '10px' }} />
              ) : (
                <StyledHeader
                  text={
                    !getUser()?.isOptimizerEnabled || activeTab === 'Overall'
                      ? TAG_OVERALL_MAP[group_by]
                      : 'Overall'
                  }
                />
              )}
              {isLoading ? (
                <PlaceholderLoader />
              ) : (
                <div className="info-card__label">
                  <p className="label-text">Successful / Total attempts</p>
                </div>
              )}
              {isLoading ? (
                <PlaceholderLoader />
              ) : (
                <div className="info-card__value">
                  <p>
                    <span className="highlight">{getFormattedNumber(data?.successful)}</span>/
                    {getFormattedNumber(data?.total)} ({data?.sr}
                    %)
                  </p>
                </div>
              )}
            </div>
          )}
          {!isEmpty(groupData) && groupData?.map(renderInfoCard)}
        </div>
      </div>
      {isEmpty(compactData) && <NoDataMessage title="No data available." />}
    </div>
  );
};

const mapStateToProps = ({ successRate }) => {
  const { isLoading, tabLoading, activeTab, tabs } = successRate;
  return {
    activeTab,
    isLoading: isLoading || tabLoading,
    tab: tabs[activeTab],
  };
};

export default connect(mapStateToProps, null)(VolumePieWidget);
